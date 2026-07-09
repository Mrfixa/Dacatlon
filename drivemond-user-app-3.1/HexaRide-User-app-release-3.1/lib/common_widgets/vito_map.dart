import 'dart:async';
import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart' as gmap;
import 'package:mapbox_maps_flutter/mapbox_maps_flutter.dart' as mbx;
import 'package:ride_sharing_user_app/features/splash/controllers/config_controller.dart';

/// Provider-agnostic map facade. Renders Google Maps by default (behaviour
/// identical to before) and Mapbox when the backend config sets
/// `map_provider == 'mapbox'`.
///
/// NOTE: the Mapbox branch is written against mapbox_maps_flutter ^2.9 and is
/// device-verified by the integrator — CI does not compile/render it. The
/// Google branch is the production default and must stay behaviour-identical.

typedef VitoMapCreatedCallback = void Function(VitoMapController controller);

/// Marker model that carries the raw icon bytes — needed because a Google
/// [gmap.Marker]'s BitmapDescriptor does not expose its bytes for Mapbox.
class VitoMarker {
  final String id;
  final gmap.LatLng position;
  final Uint8List? iconBytes;
  final double rotation;
  final Offset anchor;
  final VoidCallback? onTap;

  const VitoMarker({
    required this.id,
    required this.position,
    this.iconBytes,
    this.rotation = 0,
    this.anchor = const Offset(0.5, 0.5),
    this.onTap,
  });

  gmap.Marker toGoogleMarker() => gmap.Marker(
        markerId: gmap.MarkerId(id),
        position: position,
        icon: iconBytes != null ? gmap.BitmapDescriptor.bytes(iconBytes!) : gmap.BitmapDescriptor.defaultMarker,
        rotation: rotation,
        anchor: anchor,
        onTap: onTap,
      );
}

bool useMapboxProvider() {
  try {
    return (Get.find<ConfigController>().config?.mapProvider ?? 'google') == 'mapbox';
  } catch (_) {
    return false;
  }
}

/// Build-time Mapbox public access token, supplied by CI via
/// `--dart-define=MAPBOX_ACCESS_TOKEN=...`. Used as a fallback when the backend
/// config doesn't carry `mapbox_access_token`.
const String _mapboxAccessTokenFromEnv = String.fromEnvironment('MAPBOX_ACCESS_TOKEN', defaultValue: '');

String _mapboxAccessToken() {
  try {
    final configToken = Get.find<ConfigController>().config?.mapboxAccessToken ?? '';
    if (configToken.isNotEmpty) return configToken;
  } catch (_) {}
  return _mapboxAccessTokenFromEnv;
}

/// Thin controller abstraction over GoogleMapController / MapboxMap exposing
/// only the camera operations the app actually uses.
class VitoMapController {
  final gmap.GoogleMapController? _google;
  final mbx.MapboxMap? _mapbox;

  VitoMapController.google(this._google) : _mapbox = null;
  VitoMapController.mapbox(this._mapbox) : _google = null;

  gmap.GoogleMapController? get googleController => _google;
  mbx.MapboxMap? get mapboxController => _mapbox;

  /// Screens that used to own a raw GoogleMapController call this from their
  /// State.dispose(). The Mapbox map's lifecycle is owned by the MapWidget, so
  /// only the Google controller needs explicit disposal.
  void dispose() {
    _google?.dispose();
  }

  Future<void> animateCamera(gmap.LatLng target, {double zoom = 16, double bearing = 0, double tilt = 0}) async {
    if (_google != null) {
      await _google!.animateCamera(gmap.CameraUpdate.newCameraPosition(
        gmap.CameraPosition(target: target, zoom: zoom, bearing: bearing, tilt: tilt),
      ));
    } else if (_mapbox != null) {
      await _mapbox!.flyTo(
        mbx.CameraOptions(
          center: mbx.Point(coordinates: mbx.Position(target.longitude, target.latitude)),
          zoom: zoom,
          bearing: bearing,
          pitch: tilt,
        ),
        mbx.MapAnimationOptions(duration: 800),
      );
    }
  }


  /// Pans to [target] keeping the current zoom/bearing (Google `newLatLng`
  /// semantics) — used by follow-the-driver tracking.
  Future<void> animateToLatLng(gmap.LatLng target) async {
    if (_google != null) {
      await _google!.animateCamera(gmap.CameraUpdate.newLatLng(target));
    } else if (_mapbox != null) {
      await _mapbox!.flyTo(
        mbx.CameraOptions(center: mbx.Point(coordinates: mbx.Position(target.longitude, target.latitude))),
        mbx.MapAnimationOptions(duration: 500),
      );
    }
  }

  Future<void> moveCamera(gmap.LatLng target, {double zoom = 16, double bearing = 0, double tilt = 0}) async {
    if (_google != null) {
      await _google!.moveCamera(gmap.CameraUpdate.newCameraPosition(
        gmap.CameraPosition(target: target, zoom: zoom, bearing: bearing, tilt: tilt),
      ));
    } else if (_mapbox != null) {
      await _mapbox!.setCamera(mbx.CameraOptions(
        center: mbx.Point(coordinates: mbx.Position(target.longitude, target.latitude)),
        zoom: zoom,
        bearing: bearing,
        pitch: tilt,
      ));
    }
  }

  Future<void> fitBounds(gmap.LatLngBounds bounds, {double padding = 50}) async {
    if (_google != null) {
      await _google!.animateCamera(gmap.CameraUpdate.newLatLngBounds(bounds, padding));
    } else if (_mapbox != null) {
      final camera = await _mapbox!.cameraForCoordinateBounds(
        mbx.CoordinateBounds(
          southwest: mbx.Point(coordinates: mbx.Position(bounds.southwest.longitude, bounds.southwest.latitude)),
          northeast: mbx.Point(coordinates: mbx.Position(bounds.northeast.longitude, bounds.northeast.latitude)),
          infiniteBounds: false,
        ),
        mbx.MbxEdgeInsets(top: padding, left: padding, bottom: padding, right: padding),
        null,
        null,
        null,
        null,
      );
      await _mapbox!.flyTo(camera, mbx.MapAnimationOptions(duration: 600));
    }
  }
}

class VitoMap extends StatefulWidget {
  final gmap.LatLng initialTarget;
  final double initialZoom;
  final Set<VitoMarker> markers;

  /// Compatibility passthrough for legacy screens whose controllers build raw
  /// google_maps_flutter [gmap.Marker] sets. Rendered verbatim on the Google
  /// branch; on Mapbox the positions are rendered with the default pin (a
  /// BitmapDescriptor's bytes cannot be read back). New code should prefer
  /// [markers] with [VitoMarker.iconBytes].
  final Set<gmap.Marker> googleMarkers;
  final Set<gmap.Polyline> polylines;

  /// Zone boundaries etc. Rendered natively on Google; on Mapbox they are
  /// mirrored as polygon annotations (fill + outline).
  final Set<gmap.Polygon> googlePolygons;
  final bool myLocationEnabled;

  /// Defaults to [myLocationEnabled] when null (previous behaviour).
  final bool? myLocationButtonEnabled;
  final bool zoomControlsEnabled;
  final bool zoomGesturesEnabled;
  final bool compassEnabled;
  final bool trafficEnabled; // Google-only; Mapbox styles carry their own traffic layers.
  final bool indoorViewEnabled; // Google-only.
  final bool mapToolbarEnabled; // Google-only.
  final gmap.MinMaxZoomPreference minMaxZoomPreference;
  final VitoMapCreatedCallback? onMapCreated;
  final void Function(gmap.LatLng)? onTap;
  final void Function(gmap.CameraPosition)? onCameraMove;
  final VoidCallback? onCameraMoveStarted;
  final VoidCallback? onCameraIdle;
  final EdgeInsets padding;
  final String? googleStyleJson;
  final String? mapboxStyleUri;

  const VitoMap({
    super.key,
    required this.initialTarget,
    this.initialZoom = 14,
    this.markers = const {},
    this.googleMarkers = const {},
    this.polylines = const {},
    this.googlePolygons = const {},
    this.myLocationEnabled = false,
    this.myLocationButtonEnabled,
    this.zoomControlsEnabled = false,
    this.zoomGesturesEnabled = true,
    this.compassEnabled = false,
    this.trafficEnabled = false,
    this.indoorViewEnabled = false,
    this.mapToolbarEnabled = false,
    this.minMaxZoomPreference = gmap.MinMaxZoomPreference.unbounded,
    this.onMapCreated,
    this.onTap,
    this.onCameraMove,
    this.onCameraMoveStarted,
    this.onCameraIdle,
    this.padding = EdgeInsets.zero,
    this.googleStyleJson,
    this.mapboxStyleUri,
  });

  @override
  State<VitoMap> createState() => _VitoMapState();
}

class _VitoMapState extends State<VitoMap> {
  mbx.PointAnnotationManager? _pointManager;
  mbx.PolylineAnnotationManager? _lineManager;
  mbx.PolygonAnnotationManager? _fillManager;
  mbx.MapboxMap? _mapboxMap;
  bool _isLoading = true;
  String? _errorMessage;

  late final bool _useMapbox = useMapboxProvider();

  @override
  void initState() {
    super.initState();
    if (_useMapbox) {
      final token = _mapboxAccessToken();
      if (token.isEmpty) {
        setState(() {
          _errorMessage = 'Mapbox token not configured';
          _isLoading = false;
        });
      } else {
        mbx.MapboxOptions.setAccessToken(token);
      }
    } else {
      _isLoading = false;
    }
  }

  @override
  void dispose() {
    _pointManager?.deleteAll();
    _lineManager?.deleteAll();
    _fillManager?.deleteAll();
    _pointManager = null;
    _lineManager = null;
    _fillManager = null;
    _mapboxMap = null;
    super.dispose();
  }

  Future<void> _onMyLocationPressed() async {
    if (_mapboxMap != null) {
      // Recenter on the camera's current position at a closer zoom.
      final cameraState = await _mapboxMap!.getCameraState();
      _mapboxMap!.flyTo(
        mbx.CameraOptions(
          center: cameraState.center,
          zoom: 16,
        ),
        mbx.MapAnimationOptions(duration: 500),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_errorMessage != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 48, color: Colors.grey),
            const SizedBox(height: 16),
            Text(_errorMessage!, style: const TextStyle(color: Colors.grey)),
          ],
        ),
      );
    }

    if (!_useMapbox) {
      return gmap.GoogleMap(
        style: widget.googleStyleJson,
        initialCameraPosition: gmap.CameraPosition(target: widget.initialTarget, zoom: widget.initialZoom),
        markers: {...widget.markers.map((m) => m.toGoogleMarker()), ...widget.googleMarkers},
        polylines: widget.polylines,
        polygons: widget.googlePolygons,
        myLocationEnabled: widget.myLocationEnabled,
        myLocationButtonEnabled: widget.myLocationButtonEnabled ?? widget.myLocationEnabled,
        zoomControlsEnabled: widget.zoomControlsEnabled,
        zoomGesturesEnabled: widget.zoomGesturesEnabled,
        compassEnabled: widget.compassEnabled,
        trafficEnabled: widget.trafficEnabled,
        indoorViewEnabled: widget.indoorViewEnabled,
        mapToolbarEnabled: widget.mapToolbarEnabled,
        minMaxZoomPreference: widget.minMaxZoomPreference,
        padding: widget.padding,
        onTap: widget.onTap,
        onCameraMove: widget.onCameraMove,
        onCameraMoveStarted: widget.onCameraMoveStarted,
        onCameraIdle: widget.onCameraIdle,
        onMapCreated: (c) => widget.onMapCreated?.call(VitoMapController.google(c)),
      );
    }

    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    return Stack(
      children: [
        mbx.MapWidget(
          cameraOptions: mbx.CameraOptions(
            center: mbx.Point(coordinates: mbx.Position(widget.initialTarget.longitude, widget.initialTarget.latitude)),
            zoom: widget.initialZoom,
          ),
          styleUri: widget.mapboxStyleUri ?? (Get.isDarkMode ? mbx.MapboxStyles.DARK : mbx.MapboxStyles.STANDARD),
          onMapCreated: _onMapboxCreated,
          onTapListener: widget.onTap == null
              ? null
              : (ctx) {
                  final p = ctx.point;
                  widget.onTap!(gmap.LatLng(p.coordinates.lat.toDouble(), p.coordinates.lng.toDouble()));
                },
          onCameraChangeListener: (widget.onCameraMove == null && widget.onCameraMoveStarted == null)
              ? null
              : (_) => _emitMapboxCameraMove(),
          onMapIdleListener: (widget.onCameraIdle == null && widget.onCameraMoveStarted == null)
              ? null
              : (_) => _onMapboxIdle(),
        ),
        if (widget.myLocationEnabled)
          Positioned(
            right: 16,
            bottom: 100,
            child: FloatingActionButton.small(
              heroTag: 'vito_my_location',
              onPressed: _onMyLocationPressed,
              child: const Icon(Icons.my_location),
            ),
          ),
      ],
    );
  }

  /// Set while the Mapbox camera is between its first change event and the next
  /// map-idle event; used to synthesize onCameraMoveStarted/onCameraIdle.
  bool _mapboxCameraMoving = false;

  /// Bridges Mapbox camera changes into the Google-typed [VitoMap.onCameraMove]
  /// callback so pick-location screens work identically on both providers.
  Future<void> _emitMapboxCameraMove() async {
    final map = _mapboxMap;
    if (map == null) return;
    if (!_mapboxCameraMoving) {
      _mapboxCameraMoving = true;
      widget.onCameraMoveStarted?.call();
    }
    final callback = widget.onCameraMove;
    if (callback == null) return;
    final state = await map.getCameraState();
    if (!mounted) return;
    callback(gmap.CameraPosition(
      target: gmap.LatLng(state.center.coordinates.lat.toDouble(), state.center.coordinates.lng.toDouble()),
      zoom: state.zoom,
      bearing: state.bearing,
      tilt: state.pitch,
    ));
  }

  void _onMapboxIdle() {
    if (!_mapboxCameraMoving) return;
    _mapboxCameraMoving = false;
    widget.onCameraIdle?.call();
  }

  /// Fallback pins drawn once per colour for markers created without
  /// [VitoMarker.iconBytes] (Google renders BitmapDescriptor.defaultMarker;
  /// Mapbox has no built-in pin, so without this those markers would silently
  /// not render). Colour is derived from the marker id so pickup, destination,
  /// driver and my-location pins stay distinguishable on Mapbox.
  static final Map<int, Uint8List> _pinCache = {};

  /// Maps the marker-id vocabulary used across both apps (from/to/destination/
  /// driverPosition/rider_N/my_location/home) onto distinct pin colours.
  static Color pinColorForMarkerId(String id) {
    final key = id.toLowerCase();
    if (key.contains('driver') || key.contains('rider') || key.contains('car') || key.contains('vehicle')) {
      return const Color(0xFF4285F4); // blue — the vehicle
    }
    if (key.contains('from') || key.contains('pickup') || key.contains('home') || key.contains('sender')) {
      return const Color(0xFF34A853); // green — origin
    }
    if (key.contains('my_location') || key.contains('current')) {
      return const Color(0xFF00897B); // teal — the user
    }
    // destination / to / receiver / anything else: classic red pin.
    return const Color(0xFFEA4335);
  }

  static Future<Uint8List> _defaultMarkerBytes([Color color = const Color(0xFFEA4335)]) async {
    final cached = _pinCache[color.toARGB32()];
    if (cached != null) return cached;
    const double size = 96;
    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    final paintFill = Paint()..color = color;
    final paintStroke = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = 6;
    // Teardrop pin: circle head + triangle tail, matching the classic map pin.
    canvas.drawCircle(const Offset(size / 2, size / 2.6), size / 3.2, paintFill);
    final tail = Path()
      ..moveTo(size / 2 - size / 5, size / 2.1)
      ..lineTo(size / 2, size - 6)
      ..lineTo(size / 2 + size / 5, size / 2.1)
      ..close();
    canvas.drawPath(tail, paintFill);
    canvas.drawCircle(const Offset(size / 2, size / 2.6), size / 3.2, paintStroke);
    canvas.drawCircle(const Offset(size / 2, size / 2.6), size / 9, Paint()..color = Colors.white);
    final image = await recorder.endRecording().toImage(size.toInt(), size.toInt());
    final bytes = (await image.toByteData(format: ui.ImageByteFormat.png))!.buffer.asUint8List();
    _pinCache[color.toARGB32()] = bytes;
    return bytes;
  }

  Future<void> _onMapboxCreated(mbx.MapboxMap map) async {
    _mapboxMap = map;
    await map.location.updateSettings(mbx.LocationComponentSettings(enabled: widget.myLocationEnabled));
    _pointManager = await map.annotations.createPointAnnotationManager();
    _lineManager = await map.annotations.createPolylineAnnotationManager();
    _fillManager = await map.annotations.createPolygonAnnotationManager();
    _lastAnnotationSignature = _annotationSignature();
    await _requestAnnotationSync();
    setState(() => _isLoading = false);
    widget.onMapCreated?.call(VitoMapController.mapbox(map));
  }

  // Serializes annotation rebuilds: GetBuilder screens rebuild on every location
  // poll, and overlapping deleteAll/create rounds would race and flicker.
  bool _syncInProgress = false;
  bool _syncPending = false;

  Future<void> _requestAnnotationSync() async {
    if (_syncInProgress) {
      _syncPending = true;
      return;
    }
    _syncInProgress = true;
    try {
      do {
        _syncPending = false;
        await _syncMapboxAnnotations();
      } while (_syncPending && mounted);
    } finally {
      _syncInProgress = false;
    }
  }

  Future<void> _syncMapboxAnnotations() async {
    final pm = _pointManager;
    final lm = _lineManager;
    final fm = _fillManager;
    if (pm == null || lm == null) return;
    await pm.deleteAll();
    await lm.deleteAll();
    await fm?.deleteAll();

    for (final m in widget.markers) {
      await pm.create(mbx.PointAnnotationOptions(
        geometry: mbx.Point(coordinates: mbx.Position(m.position.longitude, m.position.latitude)),
        image: m.iconBytes ?? await _defaultMarkerBytes(pinColorForMarkerId(m.id)),
        iconRotate: m.rotation,
      ));
    }

    // Legacy google_maps_flutter markers: bytes are unreadable from a
    // BitmapDescriptor, so render their positions with a default pin whose
    // colour is derived from the marker id (pickup/destination/driver/...).
    for (final m in widget.googleMarkers) {
      await pm.create(mbx.PointAnnotationOptions(
        geometry: mbx.Point(coordinates: mbx.Position(m.position.longitude, m.position.latitude)),
        image: await _defaultMarkerBytes(pinColorForMarkerId(m.markerId.value)),
        iconRotate: m.rotation,
      ));
    }

    for (final line in widget.polylines) {
      if (line.points.length < 2) continue;
      await lm.create(mbx.PolylineAnnotationOptions(
        geometry: mbx.LineString(
          coordinates: line.points.map((p) => mbx.Position(p.longitude, p.latitude)).toList(),
        ),
        lineColor: line.color.toARGB32(),
        lineWidth: line.width.toDouble(),
      ));
    }

    // Zone overlays (out-of-zone shading etc.) — parity with the Google branch.
    if (fm != null) {
      for (final polygon in widget.googlePolygons) {
        if (polygon.points.length < 3) continue;
        await fm.create(mbx.PolygonAnnotationOptions(
          geometry: mbx.Polygon(coordinates: [
            polygon.points.map((p) => mbx.Position(p.longitude, p.latitude)).toList(),
          ]),
          // Alpha travels via fillOpacity; the colour itself is passed opaque so
          // the transparency isn't applied twice.
          fillColor: polygon.fillColor.withValues(alpha: 1).toARGB32(),
          fillOpacity: polygon.fillColor.a,
          fillOutlineColor: polygon.strokeColor.toARGB32(),
        ));
      }
    }
  }

  /// Cheap change signature over everything `_syncMapboxAnnotations` renders.
  /// Callers rebuild fresh `Set` instances every frame, so instance equality
  /// (`oldWidget.markers != widget.markers`) is always "changed" and would
  /// thrash deleteAll+create on every rebuild.
  List<Object?> _annotationSignature() => <Object?>[
        for (final m in widget.markers) ...[m.id, m.position, m.rotation, identityHashCode(m.iconBytes)],
        for (final m in widget.googleMarkers) ...[m.markerId.value, m.position, m.rotation],
        for (final l in widget.polylines) ...[
          l.polylineId.value,
          l.color.toARGB32(),
          l.width,
          l.points.length,
          if (l.points.isNotEmpty) l.points.first,
          if (l.points.isNotEmpty) l.points.last,
        ],
        for (final p in widget.googlePolygons) ...[
          p.polygonId.value,
          p.fillColor.toARGB32(),
          p.points.length,
          if (p.points.isNotEmpty) p.points.first,
        ],
      ];

  List<Object?> _lastAnnotationSignature = const [];

  @override
  void didUpdateWidget(covariant VitoMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!_useMapbox || _mapboxMap == null) return;
    final signature = _annotationSignature();
    if (!listEquals(signature, _lastAnnotationSignature)) {
      _lastAnnotationSignature = signature;
      _requestAnnotationSync();
    }
  }
}
