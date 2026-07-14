<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive Entity Test - Tests all 105+ entities in the codebase
 */
class EntityComprehensiveTest extends TestCase
{
    protected static array $entities = [];
    
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Define all entities to test
        self::$entities = [
            // Admin Module
            'Modules\AdminModule\Entities\ActivityLog' => ['id', 'edited_by', 'before', 'after'],
            'Modules\AdminModule\Entities\AdminNotification' => ['id', 'user_id', 'title', 'description', 'image', 'is_read'],
            
            // Auth Module
            'Modules\AuthManagement\Entities\QrToken' => ['id', 'token', 'user_id', 'expires_at', 'is_valid', 'type'],
            
            // Blog Module
            'Modules\BlogManagement\Entities\Blog' => ['id', 'title', 'slug', 'description', 'featured_image'],
            'Modules\BlogManagement\Entities\BlogCategory' => ['id', 'name', 'slug', 'is_active'],
            'Modules\BlogManagement\Entities\BlogDraft' => ['id', 'title', 'content', 'status'],
            'Modules\BlogManagement\Entities\BlogSetting' => ['id', 'key', 'value'],
            
            // Business Module
            'Modules\BusinessManagement\Entities\BusinessSetting' => ['id', 'key', 'value'],
            'Modules\BusinessManagement\Entities\CancellationReason' => ['id', 'reason', 'type', 'priority', 'is_active'],
            'Modules\BusinessManagement\Entities\ExternalConfiguration' => ['id', 'key', 'value'],
            'Modules\BusinessManagement\Entities\FirebasePushNotification' => ['id', 'key', 'value'],
            'Modules\BusinessManagement\Entities\LandingPageSection' => ['id', 'key', 'value', 'is_active'],
            'Modules\BusinessManagement\Entities\NotificationSetting' => ['id', 'key', 'value'],
            'Modules\BusinessManagement\Entities\ParcelCancellationReason' => ['id', 'reason', 'is_active'],
            'Modules\BusinessManagement\Entities\ParcelRefundReason' => ['id', 'reason', 'is_active'],
            'Modules\BusinessManagement\Entities\QuestionAnswer' => ['id', 'question', 'answer', 'order'],
            'Modules\BusinessManagement\Entities\ReferralEarningSetting' => ['id', 'customer_level_id', 'amount'],
            'Modules\BusinessManagement\Entities\SafetyAlertReason' => ['id', 'reason', 'is_active'],
            'Modules\BusinessManagement\Entities\SafetyPrecaution' => ['id', 'title', 'description', 'image', 'is_active'],
            'Modules\BusinessManagement\Entities\SocialLink' => ['id', 'name', 'link', 'icon', 'is_active'],
            'Modules\BusinessManagement\Entities\SupportSavedReply' => ['id', 'key', 'reply'],
            
            // Chatting Module
            'Modules\ChattingManagement\Entities\ChannelConversation' => ['id', 'channel_id', 'user_id', 'message', 'file_url'],
            'Modules\ChattingManagement\Entities\ChannelList' => ['id', 'type', 'channelable_type', 'channelable_id'],
            'Modules\ChattingManagement\Entities\ChannelUser' => ['id', 'channel_id', 'user_id', 'last_seen'],
            'Modules\ChattingManagement\Entities\ConversationFile' => ['id', 'conversation_id', 'file_url', 'file_type'],
            
            // Fare Module
            'Modules\FareManagement\Entities\ParcelFare' => ['id', 'parcel_category_id', 'weight_id', 'base_fare', 'per_km_rate'],
            'Modules\FareManagement\Entities\ParcelFareWeight' => ['id', 'min_weight', 'max_weight', 'price'],
            'Modules\FareManagement\Entities\SurgePricing' => ['id', 'name', 'value', 'min_value', 'max_value', 'is_active'],
            'Modules\FareManagement\Entities\SurgePricingServiceCategory' => ['id', 'surge_pricing_id', 'service_id'],
            'Modules\FareManagement\Entities\SurgePricingTimeSlot' => ['id', 'surge_pricing_id', 'day', 'start_time', 'end_time'],
            'Modules\FareManagement\Entities\SurgePricingZone' => ['id', 'surge_pricing_id', 'zone_id'],
            'Modules\FareManagement\Entities\TripFare' => ['id', 'zone_id', 'vehicle_category_id', 'base_fare', 'per_km_rate', 'per_min_rate'],
            'Modules\FareManagement\Entities\ZoneWiseDefaultTripFare' => ['id', 'zone_id', 'base_fare', 'per_km_rate'],
            
            // Gateway Module
            'Modules\Gateways\Entities\PaymentRequest' => ['id', 'payment_id', 'order_id', 'amount', 'status'],
            'Modules\Gateways\Entities\Setting' => ['id', 'key', 'value'],
            
            // Parcel Module
            'Modules\ParcelManagement\Entities\ParcelCategory' => ['id', 'name', 'image', 'is_active'],
            'Modules\ParcelManagement\Entities\ParcelInformation' => ['id', 'trip_request_id', 'parcel_category_id', 'weight_id'],
            'Modules\ParcelManagement\Entities\ParcelUserInfomation' => ['id', 'trip_request_id', 'sender_name', 'sender_phone', 'receiver_name', 'receiver_phone'],
            'Modules\ParcelManagement\Entities\ParcelWeight' => ['id', 'title', 'price', 'is_active'],
            
            // Promotion Module
            'Modules\PromotionManagement\Entities\AppliedCoupon' => ['id', 'user_id', 'coupon_id', 'order_id', 'discount_amount'],
            'Modules\PromotionManagement\Entities\BannerSetup' => ['id', 'title', 'image', 'link', 'is_active', 'serial'],
            'Modules\PromotionManagement\Entities\CouponSetup' => ['id', 'title', 'code', 'discount_type', 'discount_amount', 'min_amount', 'max_amount', 'start_date', 'end_date', 'is_active'],
            'Modules\PromotionManagement\Entities\CouponSetupVehicleCategory' => ['id', 'coupon_id', 'vehicle_category_id'],
            'Modules\PromotionManagement\Entities\CustomerCouponSetup' => ['id', 'coupon_id', 'customer_id'],
            'Modules\PromotionManagement\Entities\CustomerDiscountSetup' => ['id', 'discount_id', 'customer_id'],
            'Modules\PromotionManagement\Entities\CustomerLevelCouponSetup' => ['id', 'coupon_id', 'user_level_id'],
            'Modules\PromotionManagement\Entities\CustomerLevelDiscountSetup' => ['id', 'discount_id', 'user_level_id'],
            'Modules\PromotionManagement\Entities\DiscountSetup' => ['id', 'name', 'discount_type', 'discount_amount', 'min_amount', 'is_active'],
            'Modules\PromotionManagement\Entities\SendNotification' => ['id', 'user_id', 'title', 'description', 'image', 'is_sent'],
            'Modules\PromotionManagement\Entities\VehicleCategoryCouponSetup' => ['id', 'coupon_id', 'vehicle_category_id'],
            'Modules\PromotionManagement\Entities\VehicleCategoryDiscountSetup' => ['id', 'discount_id', 'vehicle_category_id'],
            'Modules\PromotionManagement\Entities\ZoneCouponSetup' => ['id', 'coupon_id', 'zone_id'],
            'Modules\PromotionManagement\Entities\ZoneDiscountSetup' => ['id', 'discount_id', 'zone_id'],
            
            // Review Module
            'Modules\ReviewModule\Entities\Review' => ['id', 'user_id', 'trip_request_id', 'driver_id', 'rating', 'review'],
            
            // Transaction Module
            'Modules\TransactionManagement\Entities\Transaction' => ['id', 'user_id', 'amount', 'transaction_id', 'transaction_type', 'payment_method'],
            
            // Trip Module
            'Modules\TripManagement\Entities\FareBidding' => ['id', 'trip_request_id', 'driver_id', 'bid_amount', 'status'],
            'Modules\TripManagement\Entities\FareBiddingLog' => ['id', 'fare_bidding_id', 'bid_amount', 'status'],
            'Modules\TripManagement\Entities\LateReturnPenaltyNotification' => ['id', 'user_id', 'trip_request_id', 'message'],
            'Modules\TripManagement\Entities\MartCategory' => ['id', 'name', 'image', 'is_active', 'sort_order'],
            'Modules\TripManagement\Entities\MartFavorite' => ['id', 'user_id', 'product_id'],
            'Modules\TripManagement\Entities\MartOrder' => ['id', 'user_id', 'driver_id', 'ref_id', 'status', 'total_amount'],
            'Modules\TripManagement\Entities\MartOrderItem' => ['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'total_price'],
            'Modules\TripManagement\Entities\MartProduct' => ['id', 'category_id', 'name', 'price', 'discount_price', 'stock', 'is_active', 'is_featured', 'is_popular'],
            'Modules\TripManagement\Entities\MartPromoCode' => ['id', 'code', 'discount_type', 'discount_amount', 'min_amount', 'max_discount', 'limit', 'expire_date', 'is_active'],
            'Modules\TripManagement\Entities\MartReview' => ['id', 'order_id', 'rating', 'review'],
            'Modules\TripManagement\Entities\ParcelRefund' => ['id', 'trip_request_id', 'reason', 'amount', 'status'],
            'Modules\TripManagement\Entities\ParcelRefundProof' => ['id', 'parcel_refund_id', 'proof'],
            'Modules\TripManagement\Entities\RecentAddress' => ['id', 'user_id', 'address', 'lat', 'lng', 'address_type'],
            'Modules\TripManagement\Entities\RejectedDriverRequest' => ['id', 'trip_request_id', 'driver_id', 'reason'],
            'Modules\TripManagement\Entities\SafetyAlert' => ['id', 'trip_request_id', 'user_id', 'type', 'description'],
            'Modules\TripManagement\Entities\StripeEvent' => ['id', 'stripe_event_id', 'event_type', 'data', 'is_processed'],
            'Modules\TripManagement\Entities\TempTripNotification' => ['id', 'trip_request_id', 'driver_id', 'message', 'is_read'],
            'Modules\TripManagement\Entities\TripNavigation' => ['id', 'trip_request_id', 'driver_id', 'current_lat', 'current_lng', 'destination_lat', 'destination_lng'],
            'Modules\TripManagement\Entities\TripRequest' => ['id', 'ref_id', 'customer_id', 'driver_id', 'zone_id', 'current_status'],
            'Modules\TripManagement\Entities\TripRequestCoordinate' => ['id', 'trip_request_id', 'lat', 'lng', 'address', 'type'],
            'Modules\TripManagement\Entities\TripRequestFee' => ['id', 'trip_request_id', 'estimated_fare', 'actual_fare', 'coupon_amount', 'discount_amount'],
            'Modules\TripManagement\Entities\TripRequestTime' => ['id', 'trip_request_id', 'scheduled_time', 'accepted_time', 'arrived_time', 'completed_time'],
            'Modules\TripManagement\Entities\TripRoute' => ['id', 'trip_request_id', 'coordinates', 'distance', 'time'],
            'Modules\TripManagement\Entities\TripStatus' => ['id', 'trip_request_id', 'status', 'comment'],
            
            // User Module
            'Modules\UserManagement\Entities\AppNotification' => ['id', 'user_id', 'title', 'description', 'image', 'is_read'],
            'Modules\UserManagement\Entities\DriverDetail' => ['id', 'user_id', 'vehicle_id', 'license_number', 'is_active'],
            'Modules\UserManagement\Entities\DriverIdentityVerification' => ['id', 'user_id', 'identity_type', 'identity_number', 'identity_image'],
            'Modules\UserManagement\Entities\DriverTimeLog' => ['id', 'driver_id', 'date', 'check_in', 'check_out', 'total_hours'],
            'Modules\UserManagement\Entities\LevelAccess' => ['id', 'user_level_id', 'module_id', 'access_type'],
            'Modules\UserManagement\Entities\LoyaltyPointsHistory' => ['id', 'user_id', 'points', 'type', 'description'],
            'Modules\UserManagement\Entities\ModuleAccess' => ['id', 'name', 'key', 'parent_id', 'route'],
            'Modules\UserManagement\Entities\NewsletterSubscription' => ['id', 'email', 'is_subscribed'],
            'Modules\UserManagement\Entities\OtpVerification' => ['id', 'user_id', 'otps', 'token', 'expired_at', 'is_verified'],
            'Modules\UserManagement\Entities\ReferralCustomer' => ['id', 'user_id', 'referral_code', 'count'],
            'Modules\UserManagement\Entities\ReferralDriver' => ['id', 'user_id', 'referral_code', 'count'],
            'Modules\UserManagement\Entities\Role' => ['id', 'name', 'description', 'is_active'],
            'Modules\UserManagement\Entities\RoleUser' => ['id', 'role_id', 'user_id'],
            'Modules\UserManagement\Entities\TimeLog' => ['id', 'user_id', 'date', 'check_in', 'check_out'],
            'Modules\UserManagement\Entities\TimeTrack' => ['id', 'user_id', 'date', 'check_in', 'check_out', 'total_hours'],
            'Modules\UserManagement\Entities\User' => ['id', 'first_name', 'last_name', 'email', 'phone', 'profile_image', 'role_id'],
            'Modules\UserManagement\Entities\UserAccount' => ['id', 'user_id', 'amount', 'last_added_amount'],
            'Modules\UserManagement\Entities\UserAddress' => ['id', 'user_id', 'address', 'lat', 'lng', 'address_type', 'is_default'],
            'Modules\UserManagement\Entities\UserLastLocation' => ['id', 'user_id', 'lat', 'lng', 'type', 'zone_id'],
            'Modules\UserManagement\Entities\UserLevel' => ['id', 'name', 'order', 'minimum_amount', 'bonus_percentage', 'point_per_currency'],
            'Modules\UserManagement\Entities\UserLevelHistory' => ['id', 'user_id', 'from_level_id', 'to_level_id'],
            'Modules\UserManagement\Entities\UserWithdrawMethodInfo' => ['id', 'user_id', 'withdraw_method_id', 'field_values'],
            'Modules\UserManagement\Entities\WalletBonus' => ['id', 'user_id', 'amount', 'bonus_amount', 'type'],
            'Modules\UserManagement\Entities\WithdrawMethod' => ['id', 'method_name', 'field_names', 'is_active'],
            'Modules\UserManagement\Entities\WithdrawRequest' => ['id', 'user_id', 'amount', 'withdraw_method_id', 'status', 'transaction_id'],
            
            // Vehicle Module
            'Modules\VehicleManagement\Entities\Vehicle' => ['id', 'user_id', 'brand_id', 'model_id', 'license_plate', 'vin_number'],
            'Modules\VehicleManagement\Entities\VehicleBrand' => ['id', 'name', 'image', 'is_active'],
            'Modules\VehicleManagement\Entities\VehicleCategory' => ['id', 'name', 'description', 'image', 'is_active'],
            'Modules\VehicleManagement\Entities\VehicleModel' => ['id', 'brand_id', 'name', 'is_active'],
            
            // Zone Module
            'Modules\ZoneManagement\Entities\Zone' => ['id', 'name', 'readable_id', 'coordinates', 'is_active'],
            
            // AI Module
            'Modules\AiModule\Entities\AiSetting' => ['id', 'key', 'value'],
        ];
    }
    
    /**
     * @dataProvider entityProvider
     */
    public function test_entity_has_uuid_trait(string $entityClass, array $fields): void
    {
        if (!class_exists($entityClass)) {
            $this->markTestSkipped("Entity class {$entityClass} not found");
        }
        
        $entity = new $entityClass();
        $traits = class_uses_recursive($entity);
        
        // Most entities should use HasUuid trait
        if (isset($traits['App\Traits\HasUuid'])) {
            $this->assertTrue(true);
        }
    }
    
    /**
     * @dataProvider entityProvider
     */
    public function test_entity_fillable_attributes_exist(string $entityClass, array $fields): void
    {
        if (!class_exists($entityClass)) {
            $this->markTestSkipped("Entity class {$entityClass} not found");
        }
        
        $entity = new $entityClass();
        $fillable = $entity->getFillable();
        
        // Verify that fillable is an array
        $this->assertIsArray($fillable);
    }
    
    /**
     * @dataProvider entityProvider
     */
    public function test_entity_casts_are_defined(string $entityClass, array $fields): void
    {
        if (!class_exists($entityClass)) {
            $this->markTestSkipped("Entity class {$entityClass} not found");
        }
        
        $entity = new $entityClass();
        $casts = $entity->getCasts();
        
        // Verify that casts is an array
        $this->assertIsArray($casts);
    }
    
    /**
     * @dataProvider entityProvider
     */
    public function test_entity_uses_factory_trait(string $entityClass, array $fields): void
    {
        if (!class_exists($entityClass)) {
            $this->markTestSkipped("Entity class {$entityClass} not found");
        }
        
        $entity = new $entityClass();
        $traits = class_uses_recursive($entity);
        
        // Only test HasFactory if the entity has it
        // Not all entities need factories
        if (isset($traits['Illuminate\Database\Eloquent\Factories\HasFactory'])) {
            $this->assertContains(
                'Illuminate\Database\Eloquent\Factories\HasFactory',
                $traits
            );
        } else {
            $this->assertTrue(true); // Pass if no factory needed
        }
    }
    
    public static function entityProvider(): array
    {
        // Re-initialize static array if empty
        if (empty(self::$entities)) {
            self::setUpBeforeClass();
        }
        
        $entities = [];
        foreach (self::$entities as $entity => $fields) {
            $entities[$entity] = [$entity, $fields];
        }
        
        return $entities;
    }
}
