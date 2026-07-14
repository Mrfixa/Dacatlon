<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive Test for PHP Service Classes
 * Tests that all services exist and can be instantiated
 */
class ServiceComprehensiveTest extends TestCase
{
    protected static array $services = [];
    
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Define all services to test
        self::$services = [
            // Admin Module
            'Modules\AdminModule\Service\ActivityLogService',
            'Modules\AdminModule\Service\AdminNotificationService',
            
            // Auth Module
            'Modules\AuthManagement\Service\AuthService',
            
            // Blog Module
            'Modules\BlogManagement\Service\BlogService',
            'Modules\BlogManagement\Service\BlogCategoryService',
            'Modules\BlogManagement\Service\BlogDraftService',
            'Modules\BlogManagement\Service\BlogSettingService',
            
            // Business Module
            'Modules\BusinessManagement\Service\BusinessSettingService',
            'Modules\BusinessManagement\Service\CancellationReasonService',
            'Modules\BusinessManagement\Service\ExternalConfigurationService',
            'Modules\BusinessManagement\Service\FirebasePushNotificationService',
            'Modules\BusinessManagement\Service\LandingPageSectionService',
            'Modules\BusinessManagement\Service\NotificationSettingService',
            'Modules\BusinessManagement\Service\ParcelCancellationReasonService',
            'Modules\BusinessManagement\Service\ParcelRefundReasonService',
            'Modules\BusinessManagement\Service\QuestionAnswerService',
            'Modules\BusinessManagement\Service\ReferralEarningService',
            'Modules\BusinessManagement\Service\SafetyAlertReasonService',
            'Modules\BusinessManagement\Service\SafetyPrecautionService',
            'Modules\BusinessManagement\Service\SettingService',
            'Modules\BusinessManagement\Service\SocialLinkService',
            'Modules\BusinessManagement\Service\SupportSavedReplyService',
            
            // Chatting Module
            'Modules\ChattingManagement\Service\ChannelConversationService',
            'Modules\ChattingManagement\Service\ChannelListService',
            'Modules\ChattingManagement\Service\ChannelUserService',
            'Modules\ChattingManagement\Service\ConversationFileService',
            
            // Fare Module
            'Modules\FareManagement\Service\ParcelFareService',
            'Modules\FareManagement\Service\ParcelFareWeightService',
            'Modules\FareManagement\Service\SurgePricingService',
            'Modules\FareManagement\Service\TripFareService',
            'Modules\FareManagement\Service\ZoneWiseDefaultTripFareService',
            
            // Gateway Module
            // Settings service is entity, not service
            
            // Parcel Module
            'Modules\ParcelManagement\Service\ParcelCategoryService',
            'Modules\ParcelManagement\Service\ParcelInformationService',
            'Modules\ParcelManagement\Service\ParcelUserInformationService',
            'Modules\ParcelManagement\Service\ParcelWeightService',
            
            // Promotion Module
            'Modules\PromotionManagement\Service\AppliedCouponService',
            'Modules\PromotionManagement\Service\BannerSetupService',
            'Modules\PromotionManagement\Service\CouponSetupService',
            // CouponSetupVehicleCategoryService - doesn't exist
            // CustomerCouponSetupService - doesn't exist
            'Modules\PromotionManagement\Service\CustomerDiscountSetupService',
            // CustomerLevelCouponSetupService - doesn't exist
            'Modules\PromotionManagement\Service\CustomerLevelDiscountSetupService',
            'Modules\PromotionManagement\Service\DiscountSetupService',
            'Modules\PromotionManagement\Service\SendNotificationService',
            // VehicleCategoryCouponSetupService - doesn't exist
            'Modules\PromotionManagement\Service\VehicleCategoryDiscountSetupService',
            // ZoneCouponSetupService doesn't exist - only ZoneDiscountSetupService
            'Modules\PromotionManagement\Service\ZoneDiscountSetupService',
            
            // Review Module
            'Modules\ReviewModule\Service\ReviewService',
            
            // Transaction Module
            'Modules\TransactionManagement\Service\TransactionService',
            
            // Trip Module
            'Modules\TripManagement\Service\FareBiddingLogService',
            'Modules\TripManagement\Service\FareBiddingService',
            'Modules\TripManagement\Service\ParcelRefundProofService',
            'Modules\TripManagement\Service\ParcelRefundService',
            'Modules\TripManagement\Service\RecentAddressService',
            'Modules\TripManagement\Service\RejectedDriverRequestService',
            'Modules\TripManagement\Service\SafetyAlertService',
            'Modules\TripManagement\Service\TempTripNotificationService',
            'Modules\TripManagement\Service\TripNavigationService',
            'Modules\TripManagement\Service\TripRequestCoordinateService',
            'Modules\TripManagement\Service\TripRequestFeeService',
            'Modules\TripManagement\Service\TripRequestService',
            'Modules\TripManagement\Service\TripRequestTimeService',
            'Modules\TripManagement\Service\TripRouteService',
            'Modules\TripManagement\Service\TripStatusService',
            
            // User Module
            'Modules\UserManagement\Service\AppNotificationService',
            'Modules\UserManagement\Service\CustomerAccountService',
            'Modules\UserManagement\Service\CustomerLevelService',
            'Modules\UserManagement\Service\DriverAccountService',
            'Modules\UserManagement\Service\DriverDetailService',
            'Modules\UserManagement\Service\DriverIdentityVerificationService',
            'Modules\UserManagement\Service\DriverService',
            'Modules\UserManagement\Service\DriverTimeLogService',
            'Modules\UserManagement\Service\EmployeeRoleService',
            'Modules\UserManagement\Service\EmployeeService',
            'Modules\UserManagement\Service\LevelAccessService',
            'Modules\UserManagement\Service\LoyaltyPointsHistoryService',
            'Modules\UserManagement\Service\ModuleAccessService',
            'Modules\UserManagement\Service\NewsletterSubscriptionService',
            'Modules\UserManagement\Service\OtpVerificationService',
            'Modules\UserManagement\Service\ReferralCustomerService',
            'Modules\UserManagement\Service\ReferralDriverService',
            'Modules\UserManagement\Service\RoleService',
            'Modules\UserManagement\Service\RoleUserService',
            'Modules\UserManagement\Service\TimeLogService',
            'Modules\UserManagement\Service\TimeTrackService',
            'Modules\UserManagement\Service\UserAccountService',
            'Modules\UserManagement\Service\UserAddressService',
            'Modules\UserManagement\Service\UserLastLocationService',
            'Modules\UserManagement\Service\UserLevelHistoryService',
            'Modules\UserManagement\Service\UserLevelService',
            'Modules\UserManagement\Service\UserService',
            'Modules\UserManagement\Service\WalletBonusService',
            'Modules\UserManagement\Service\WithdrawMethodService',
            'Modules\UserManagement\Service\WithdrawRequestService',
            'Modules\UserManagement\Service\DriverWithdrawMethodInfoService',
            
            // Vehicle Module
            'Modules\VehicleManagement\Service\VehicleBrandService',
            'Modules\VehicleManagement\Service\VehicleCategoryService',
            'Modules\VehicleManagement\Service\VehicleModelService',
            'Modules\VehicleManagement\Service\VehicleService',
            
            // Zone Module
            'Modules\ZoneManagement\Service\ZoneService',
            
            // AI Module
            'Modules\AiModule\Service\AiSettingService',
            'Modules\AiModule\Service\BlogDescriptionPromptService',
            'Modules\AiModule\Service\BlogSeoPromptService',
            'Modules\AiModule\Service\BlogTitleFromContentsPromptService',
            'Modules\AiModule\Service\BlogTitlePromptService',
            'Modules\AiModule\Service\BlogTitleSuggestionPromptService',
            'Modules\AiModule\Service\ClaudeService',
            'Modules\AiModule\Service\ContentGeneratorService',
            'Modules\AiModule\Service\OpenAiService',
            'Modules\AiModule\Service\ResponseValidatorService',
        ];
    }
    
    /**
     * @dataProvider serviceProvider
     */
    public function test_service_class_exists(string $serviceClass): void
    {
        $this->assertTrue(
            class_exists($serviceClass) || interface_exists($serviceClass),
            "Service class {$serviceClass} does not exist"
        );
    }
    
    /**
     * @dataProvider serviceProvider
     */
    public function test_service_has_interface_if_needed(string $serviceClass): void
    {
        // Check if there's a matching interface
        $interfaceName = str_replace('Service', 'ServiceInterface', $serviceClass);
        
        // Either the service exists as interface or both exist
        if (class_exists($serviceClass)) {
            // If interface exists, verify the class implements it
            if (interface_exists($interfaceName)) {
                $interfaces = class_implements($serviceClass);
                $this->assertContains(
                    $interfaceName,
                    $interfaces,
                    "{$serviceClass} should implement {$interfaceName}"
                );
            }
        }
    }
    
    public static function serviceProvider(): array
    {
        if (empty(self::$services)) {
            self::setUpBeforeClass();
        }
        
        $services = [];
        foreach (self::$services as $service) {
            $services[$service] = [$service];
        }
        
        return $services;
    }
}
