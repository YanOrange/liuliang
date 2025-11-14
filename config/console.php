<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'InsertOppoPlanDetail' => 'app\command\InsertPlanDetailDataCmd',
        'InsertVivoPlanDetailData' => 'app\command\InsertVivoPlanDetailDataCmd',
        'InsertOppoPlanDetailData' => 'app\command\InsertOppoPlanDetailDataCmd',
        'MerchantThreadNumsHistory' => 'app\command\MerchantThreadNumsHistoryCmd',
        'UserListSetStatus' => 'app\command\UserListSetStatusCmd',
        'ClearMerchantSwitchTimeCrontab' => 'app\command\ClearMerchantSwitchTime',
        'RobotSubscribe' => 'app\command\RedisRobotSubscribe',
        'MarketStatistCmd' => 'app\command\MarketStatistCmd',
        'AssignMerchantRateThread' => 'app\command\AssignMerchantRateThreadCmd',
        'TenimImportAccount' => 'app\command\TenimImportAccount',
        'AutoBroadPlanConsume' => 'app\command\AutoBroadPlanConsumeData',
        'OppoOwnerBalance' => 'app\command\OppoOwnerBalance',
        'OppoAccountConsumeBillHis' => 'app\command\OppoAccountConsumeBillHis',
        'InsertVivoPlanFinanceBill' => 'app\command\InsertVivoPlanFinanceBill',
        'InsertVivoPlanFinanceAmount' => 'app\command\InsertVivoPlanFinanceAmount',
        'InsertVivoPlanDetailDay' => 'app\command\InsertVivoPlanDetailDayCmd',
        'InsertOppoPlanDetailDay' => 'app\command\InsertOppoPlanDetailDayCmd',
        'BiTrafficForecast' => 'app\command\BiTrafficForecastCmd',
        'InsertVivoPlanFinanceQuery' => 'app\command\InsertVivoPlanFinanceQuery',
        'MerchantSearchRatio' => 'app\command\MerchantSearchRatioCmd',
        'TodayMerchant' => 'app\command\TodayMerchantCmd',
        'CheckThreadDiscernStatus' => 'app\command\CheckThreadDiscernStatus',
        'InsertVivoPlanDetailDiffData' => 'app\command\InsertVivoPlanDetailDiffDataCmd',
        'InsertDouyinPlanDetailDay' => 'app\command\InsertDouyinPlanDetailDay',
        'InsertDouyinAdvertiserDailyBill' => 'app\command\InsertDouyinAdvertiserDailyBill',
        'BroadcastMerchant' => 'app\command\BroadcastMerchant',
        'InsertDouyinAdReportDay' => 'app\command\InsertDouyinAdReportDay',
        'ServiceFromAssignMerchantThread' => 'app\command\ServiceFormAssignMerchantThreadCmd',
        'CollectionOrderSettlement' => 'app\command\DistributionCollectionOrderSettlement',
        'CheckYqWxMiniStatus' => 'app\command\CheckYqWxMiniStatus',
        'LearnCourseSectionLiveTime' => 'app\command\LearnCourseSectionLiveTime',
        'LearnFeedbackAutoReply' => 'app\command\LearnFeedbackAutoReply',
        'InitCustomerComponentRule' => 'app\command\InitCustomerComponentRule',
        'InsertBaiduCostDetailDay' => 'app\command\InsertBaiduCostDetailDay',
        'MerchantResidueAmount' => 'app\command\MerchantResidueAmountCmd',
        'RegisterThread' => 'app\command\RegisterThread',
        'GdtThreadCallback' => 'app\command\GdtThreadCallback',
        'JzdAutoRegisterThread' => 'app\command\JzdAutoRegisterThreadCmd',
        'JzdCustomerAutoInput' => 'app\command\JzdCustomerAutoInputCmd',
        'MerchantPretopUp' => 'app\command\MerchantPretopUp',
        'CustomerAddThreadNum' => 'app\command\CustomerAddThreadNumCmd',
        'JzdCustomerThreadTime' => 'app\command\JzdCustomerThreadTime',
        'OverdueMerchantThread' => 'app\command\OverdueMerchantThread',
        'OverdueMerchantThreadRegister' => 'app\command\OverdueMerchantThreadRegister',
        'GdtMarkingPushThread' => 'app\command\GdtMarkingPushThreadCmd',
        'OverduePhoneThread' => 'app\command\OverduePhoneThread',

        'CallbackDouYinDealUser' => 'app\command\CallbackDouYinDealUserCmd',
        'WecomCustomer' => 'app\command\WecomCustomerCmd',
        'WecomCustomerSyncThread' => 'app\command\WecomCustomerSyncThreadCmd',
        'SyncZyxCustomer' => 'app\command\SyncZyxCustomerCmd',

        # 同步快手房贷线索
//        'KuaiShouCustomerSyncThreadCmd' => 'app\command\KuaiShouCustomerSyncThreadCmd', # 废弃
        'SyncThreadKuaiShouCmd' => 'app\command\syncThread\SyncThreadKuaiShouCmd',
        'SyncThreadDouYinCmd' => 'app\command\syncThread\SyncThreadDouYinCmd',

        # 数据回传上报
        'CallbackDouYinCmd' => 'app\command\callback\CallbackDouYinCmd',

        # 更新user_list加微状态
        'UpdateUserListWecomStatusCmd' => 'app\command\userList\UpdateUserListWecomStatusCmd',

        # 线索同步至宇络
        'ThreadSyncToYuluoCmd' => 'app\command\ThreadSyncToYuluoCmd',
        'SyncJzlThreadKuaiShouCmd' => 'app\command\syncThread\SyncJzlThreadKuaiShouCmd',
        'SyncJzlTmpThreadKuaiShouCmd' => 'app\command\syncThread\SyncJzlTmpThreadKuaiShouCmd',

        'SyncThreadYiliaoCmd' => 'app\command\syncThread\SyncThreadYiliaoCmd',

        'WecomSyncCustomer' => 'app\command\wecom\WecomSyncCustomer',



    ],
];
