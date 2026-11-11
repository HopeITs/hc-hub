<?php

return [
    'titles' => [
        'index' => 'العقود',
        'show' => 'العقد',
        'form' => 'نموذج العقد',
    ],

    'search' => 'البحث في العقود',
    'period' => 'المدة',
    'auto_renews' => 'يتجدد تلقائياً',
    'yes' => 'نعم',
    'no' => 'لا',

    'views' => [
        'active' => 'سارية',
        'ending-soon' => 'تنتهي قريباً',
        'ended' => 'منتهية',
        'not-renewed' => 'لم تُجدَّد',
        'draft' => 'مسودات',
    ],

    'status' => [
        'draft' => 'مسودة',
        'terminated' => 'منهى',
        'active' => 'ساري',
        'expired' => 'منتهي',
        'inactive' => 'غير ساري',
    ],

    'filters' => [
        'all_types' => 'كل أنواع العقود',
    ],

    'fields' => [
        'employee_id' => 'الموظف',
        'position_id' => 'المنصب',
        'contract_type_id' => 'نوع العقد',
        'type' => 'نوع الدوام',
        'place' => 'مكان العمل',
        'is_active' => 'ساري',
        'auto_renew' => 'التجديد التلقائي',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'terminated_at' => 'تاريخ الإنهاء',
        'termination_reason_id' => 'سبب الإنهاء',
        'reason_of_termination' => 'ملاحظات الإنهاء',
        'weekly_hours' => 'الساعات الأسبوعية',
        'schedule_mode' => 'الدوام',
        'work_hours_from' => 'بداية الدوام',
        'work_hours_to' => 'نهاية الدوام',
        'work_hours_2_from' => 'بداية الفترة الثانية',
        'work_hours_2_to' => 'نهاية الفترة الثانية',
        'work_day_from' => 'أول يوم عمل',
        'work_day_to' => 'آخر يوم عمل',
        'show_days_per_week' => 'عدد الأيام في الأسبوع',
        'show_hours_per_week' => 'عدد الساعات في الأسبوع',
        'show_hours_per_day' => 'عدد الساعات في اليوم',
        'show_day_range' => 'أيام العمل',
        'flex_days_per_week' => 'الأيام في الأسبوع',
        'flex_hours_per_week' => 'الساعات في الأسبوع',
        'flex_hours_per_month' => 'الساعات في الشهر',
        'flex_varies_by_requirements' => 'يختلف حسب العمل',
        'flex_coordinated_with_manager' => 'بالتنسيق مع المدير',
        'flex_custom_en' => 'ملاحظة الدوام (إنكليزي)',
        'flex_custom_ar' => 'ملاحظة الدوام (عربي)',
        'pay_mode' => 'نوع الأجر',
        'currency' => 'العملة',
        'salary' => 'الأجر',
        'salary_per' => 'الأجر لكل',
        'session_duration' => 'مدة الجلسة',
        'pay_tiers' => 'شرائح الأجر',
        'condition_en' => 'الشرط (إنكليزي)',
        'condition_ar' => 'الشرط (عربي)',
        'amount' => 'المبلغ',
    ],

    'form' => [
        'create_title' => 'عقد جديد',
        'edit_title' => 'تعديل العقد',
        'renew_title' => 'تجديد العقد',
        'identity' => 'العقد',
        'schedule' => 'الدوام',
        'pay' => 'الأجر',
        'pay_hidden' => 'الأجر غير ظاهر لدورك. يحتفظ الحفظ بالأجر المسجل.',
        'end_date_help' => 'مدة العقد سنة واحدة على الأكثر.',
        'show_in_clause' => 'إظهار في نص العقد',
    ],

    'schedule' => [
        'fixed' => 'دوام ثابت',
        'flexible' => 'دوام مرن',
        'days' => 'أيام العمل',
        'hours' => 'ساعات العمل',
        'custom' => 'ملاحظة الدوام',
    ],

    'days' => [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الاثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
    ],

    'actions' => [
        'add' => 'إضافة عقد',
        'save' => 'حفظ العقد',
        'edit' => 'تعديل',
        'renew' => 'تجديد',
        'terminate' => 'إنهاء',
        'print' => 'طباعة',
        'open_employee' => 'ملف الموظف',
        'add_tier' => 'إضافة شريحة',
        'stop_auto_renew' => 'إيقاف',
        'start_auto_renew' => 'تفعيل',
    ],

    'toasts' => [
        'saved' => 'تم حفظ العقد.',
        'terminated' => 'تم إنهاء العقد.',
        'deleted' => 'تم حذف العقد.',
        'delete_blocked' => 'لهذا العقد سجلات مرتبطة (أرصدة إجازات، ملاحق) ولا يمكن حذفه.',
        'auto_renew_changed' => 'تم تحديث التجديد التلقائي.',
    ],

    'confirm' => [
        'delete_title' => 'حذف هذا العقد؟',
        'delete_body' => 'سيُحذف العقد ويتحرر مقعده في المنصب. لا يمكن التراجع عن ذلك.',
    ],

    'errors' => [
        'position_full' => 'لا يوجد مقعد شاغر في هذا المنصب.',
        'employee_incomplete' => 'سجل الموظف مسودة. أكمله قبل إضافة عقد.',
        'service_requires_freelance' => 'عقد الخدمة يجب أن يكون دواماً حراً.',
        'freelance_requires_service' => 'الدوام الحر لعقود الخدمة فقط.',
        'weekly_hours_required' => 'أدخل الساعات الأسبوعية (عقود الخدمة فقط يمكن أن تبقى 0).',
        'pay_hidden_new' => 'لا يستطيع دورك تحديد الأجر، لذا لا يمكنه إنشاء عقد جديد. راجع الموارد البشرية.',
        'cannot_auto_renew' => 'لا يمكن تجديد عقد منهى أو عقد تجربة تلقائياً.',
    ],

    'empty' => [
        'heading' => 'لا توجد عقود',
        'body' => 'تظهر هنا عقود هذا العرض.',
    ],

    'print' => [
        'title' => 'عقد عمل',
        'employee' => 'الموظف',
        'employee_signature' => 'توقيع الموظف',
        'employer_signature' => 'عن مركز الأمل',
    ],
];
