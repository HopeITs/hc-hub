<?php

return [
    'titles' => [
        'index' => 'المناصب',
        'team' => 'مخطط الفريق',
    ],

    'search' => 'البحث في المناصب',
    'search_placeholder' => 'اسم المنصب',
    'reports_to' => 'يتبع لـ :title',

    'status' => [
        'active' => 'فعالة',
        'inactive' => 'غير فعالة',
        'all' => 'الكل',
    ],

    'filters' => [
        'any_seats' => 'كل المقاعد',
        'open_seats' => 'فيها مقعد شاغر',
    ],

    'fields' => [
        'title' => 'المنصب',
        'title_en' => 'المسمى (إنكليزي)',
        'title_ar' => 'المسمى (عربي)',
        'program_id' => 'البرنامج',
        'city_id' => 'المدينة',
        'center_id' => 'المركز',
        'department_id' => 'القسم',
        'job_level_id' => 'المستوى الوظيفي',
        'direct_manager_id' => 'المدير المباشر',
        'tech_manager_id' => 'المدير الفني',
        'qualifications' => 'المؤهلات',
        'responsibilities' => 'المسؤوليات',
        'max_emp' => 'المقاعد',
        'seats' => 'المقاعد',
        'is_active' => 'فعال',
    ],

    'form' => [
        'no_center' => 'بلا مركز (عن بعد)',
        'no_manager' => 'بلا مدير',
    ],

    'actions' => [
        'open_vacancy' => 'فتح شاغر',
        'open_vacancy_name' => 'فتح شاغر في :name',
        'add' => 'إضافة منصب',
        'edit' => 'تعديل المنصب',
        'toggle_name' => 'تفعيل أو إيقاف :name',
    ],

    'critical' => [
        'title' => 'المناصب الحرجة',
        'critical' => 'حرج',
        'mark' => 'وسم كمنصب حرج',
        'unmark' => 'إزالة وسم الحرج',
        'marked' => 'أصبح المنصب حرجاً.',
        'unmarked' => 'لم يعد المنصب حرجاً.',
        'is_critical_help' => 'هذا المنصب حرج: تُنبَّه الموارد البشرية عندما يخلو من شاغل.',
        'not_critical_help' => 'سِم هذا المنصب كحرج لتُنبَّه الموارد البشرية عندما يخلو من شاغل.',
        'none_title' => 'لا توجد مناصب حرجة',
        'none_help' => 'تظهر هنا المناصب الموسومة كحرجة ضمن نطاقك.',
        'holders' => 'الشاغلون',
        'needs_replacement' => 'يحتاج بديلاً',
        'suggested_replacements' => 'البدلاء المقترحون',
        'panel_title' => 'البدلاء المقترحون',
        'suggestions_only' => 'هذه اقتراحات فقط. لا تتم أي ترقية أو تعيين من هذه الصفحة.',
        'reason' => [
            'vacant' => 'هذا المنصب شاغر.',
            'contract_ended' => 'انتهى عقد آخر شاغل ولا أحد يشغل المنصب الآن.',
        ],
        'internal_title' => 'من داخل المؤسسة',
        'internal_empty' => 'لا يوجد متطوع مناسب',
        'internal_empty_help' => 'لا أحد ضمن نطاقك يبلغ الحد الأدنى من الدرجة حتى الآن.',
        'score' => 'الدرجة :score',
        'open_profile' => 'فتح الملف الشخصي',
        'reasons' => [
            'level' => 'المستوى الوظيفي :level (+:points)',
            'title' => 'يشغل منصباً مشابهاً: :title (+:points)',
            'background' => 'تطابق في التعليم أو الدورات أو الخبرة: :matched (+:points)',
            'service' => ':years سنة في المؤسسة (+:points)',
            'department' => 'القسم نفسه (+:points)',
            'same_city' => 'المدينة نفسها (+:points)',
            'transfer' => 'يحتاج إلى الانتقال إلى مدينة أخرى',
        ],
        'cv_title' => 'من السير الذاتية السابقة',
        'cv_empty' => 'بانتظار رفع السير الذاتية',
        'cv_empty_help' => 'لا يوجد متقدمون سابقون بلغوا المراحل الأخيرة متاحون لك.',
        'open_candidate' => 'فتح ملف المرشح',
        'cv' => [
            'reached' => 'بلغ المرحلة: :stage',
            'same_position' => 'تقدّم لهذا المنصب نفسه',
            'similar_position' => 'تقدّم لمنصب مشابه: :position',
        ],
    ],

    'toasts' => [
        'saved' => 'تم حفظ المنصب.',
        'deleted' => 'تم حذف المنصب.',
        'delete_blocked' => 'لهذا المنصب عقود أو مناصب تابعة ولا يمكن حذفه. أوقفه بدلاً من ذلك.',
        'status_changed' => 'تم تغيير حالة المنصب.',
    ],

    'confirm' => [
        'delete_title' => 'حذف :name؟',
        'delete_body' => 'سيُحذف المنصب. لا يمكن التراجع عن ذلك.',
        'toggle_title' => 'تغيير حالة :name؟',
        'toggle_body' => 'لا يُعرض المنصب غير الفعال للعقود الجديدة. تبقى العقود الحالية.',
    ],

    'errors' => [
        'city_scope' => 'يمكنك إدارة مناصب مدنك فقط.',
        'center_city' => 'يجب أن يكون المركز في مدينة المنصب.',
    ],

    'empty' => [
        'heading' => 'لا توجد مناصب',
        'body' => 'تظهر هنا مناصب مدنك.',
    ],

    'team' => [
        'email_team' => 'مراسلة الفريق',
        'email_team_directly' => 'مراسلة الفريق مباشرة',
        'request_hire' => 'طلب توظيف',
        'positions' => 'المناصب',
        'open_seats' => 'المقاعد الشاغرة',
        'scope' => 'العرض',
        'my_team' => 'فريقي',
        'whole' => 'المنظمة كاملة',
        'open_only' => 'الفروع التي فيها مقاعد شاغرة فقط',
        'open_count' => '{0} لا مقاعد شاغرة|{1} مقعد شاغر واحد|{2} مقعدان شاغران|[3,10] :count مقاعد شاغرة|[11,*] :count مقعداً شاغراً',
        'reports' => '{0} لا تابعين|{1} تابع واحد|{2} تابعان|[3,10] :count تابعين|[11,*] :count تابعاً',
        'empty_heading' => 'لا شيء للعرض',
        'empty_body' => 'لا توجد مناصب تطابق هذا العرض.',
    ],
];
