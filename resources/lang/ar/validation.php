<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute في حالة :other يساوي :value.',
    'active_url' => 'حقل :attribute لا يُمثّل رابطًا صحيحًا.',
    'after' => 'يجب على حقل :attribute أن يكون تاريخًا لاحقًا للتاريخ :date.',
    'after_or_equal' => 'حقل :attribute يجب أن يكون تاريخاً لاحقاً أو مطابقاً للتاريخ :date.',
    'alpha' => 'يجب أن لا يحتوي حقل :attribute سوى على حروف.',
    'alpha_dash' => 'يجب أن لا يحتوي حقل :attribute سوى على حروف، أرقام ومطّات.',
    'alpha_num' => 'يجب أن يحتوي حقل :attribute على حروفٍ وأرقامٍ فقط.',
    'array' => 'يجب أن يكون حقل :attribute ًمصفوفة.',
    'before' => 'يجب على حقل :attribute أن يكون تاريخًا سابقًا للتاريخ :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'file' => 'يجب أن يكون حجم ملف حقل :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute بين :min و :max.',
        'string' => 'يجب أن يكون عدد حروف نّص حقل :attribute بين :min و :max.',
        'array' => 'يجب أن يحتوي حقل :attribute على عدد من العناصر بين :min و :max.',
    ],
    'boolean' => 'يجب أن تكون قيمة حقل :attribute إما true أو false .',
    'confirmed' => 'حقل التأكيد غير مُطابق لحقل :attribute.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'حقل :attribute ليس تاريخًا صحيحًا.',
    'date_equals' => 'يجب أن يكون حقل :attribute مطابقاً للتاريخ :date.',
    'date_format' => 'لا يتوافق حقل :attribute مع الشكل :format.',
    'different' => 'يجب أن يكون الحقلان :attribute و :other مُختلفين.',
    'digits' => 'يجب أن يحتوي حقل :attribute على :digits رقمًا/أرقام.',
    'digits_between' => 'يجب أن يحتوي حقل :attribute بين :min و :max رقمًا/أرقام .',
    'dimensions' => 'الحقل:attribute يحتوي على أبعاد صورة غير صالحة.',
    'distinct' => 'للحقل :attribute قيمة مُكرّرة.',
    'email' => 'يجب أن يكون حقل :attribute عنوان بريد إلكتروني صحيح البُنية.',
    'ends_with' => 'يجب أن ينتهي حقل :attribute بأحد القيم التالية: :values',
    'exists' => 'القيمة المحددة :attribute غير موجودة.',
    'failed' => 'بيانات الاعتماد هذه غير متطابقة مع البيانات المسجلة لدينا.',
    'file' => 'الحقل :attribute يجب أن يكون ملفا.',
    'filled' => 'حقل :attribute إجباري.',
    'gt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أكثر من :value عناصر/عنصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول نّص حقل :attribute أكثر من :value حروفٍ/حرفًا.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي حقل :attribute على الأقل على :value عُنصرًا/عناصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute على الأقل :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute مساوية أو أكبر من :value.',
        'string' => 'يجب أن يكون طول نص حقل :attribute على الأقل :value حروفٍ/حرفًا.',
    ],
    'image' => 'يجب أن يكون حقل :attribute صورةً.',
    'in' => 'حقل :attribute غير موجود.',
    'in_array' => 'حقل :attribute غير موجود في :other.',
    'integer' => 'يجب أن يكون حقل :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون حقل :attribute عنوان IP صحيحًا.',
    'ipv4' => 'يجب أن يكون حقل :attribute عنوان IPv4 صحيحًا.',
    'ipv6' => 'يجب أن يكون حقل :attribute عنوان IPv6 صحيحًا.',
    'json' => 'يجب أن يكون حقل :attribute نصًا من نوع JSON.',
    'lt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أقل من :value عناصر/عنصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute أصغر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أصغر من :value.',
        'string' => 'يجب أن يكون طول نّص حقل :attribute أقل من :value حروفٍ/حرفًا.',
    ],
    'lte' => [
        'array' => 'يجب أن لا يحتوي حقل :attribute على أكثر من :value عناصر/عنصر.',
        'file' => 'يجب أن لا يتجاوز حجم ملف حقل :attribute :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute مساوية أو أصغر من :value.',
        'string' => 'يجب أن لا يتجاوز طول نّص حقل :attribute :value حروفٍ/حرفًا.',
    ],
    'max' => [
        'array' => 'يجب أن لا يحتوي حقل :attribute على أكثر من :max عناصر/عنصر.',
        'file' => 'يجب أن لا يتجاوز حجم ملف حقل :attribute :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute مساوية أو أصغر من :max.',
        'string' => 'يجب أن لا يتجاوز طول نّص حقل :attribute :max حروفٍ/حرفًا.',
    ],
    'mimes' => 'يجب أن يكون ملفًا من نوع : :values.',
    'mimetypes' => 'يجب أن يكون ملفًا من نوع : :values.',
    'min' => [
        'array' => 'يجب أن يحتوي حقل :attribute على الأقل على :min عُنصرًا/عناصر.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute على الأقل :min كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute مساوية أو أكبر من :min.',
        'string' => 'يجب أن يكون طول نص حقل :attribute على الأقل :min حروفٍ/حرفًا.',
    ],
    'not_in' => 'عنصر الحقل :attribute غير صحيح.',
    'not_regex' => 'صيغة حقل :attribute غير صحيحة.',
    'numeric' => 'يجب على حقل :attribute أن يكون رقمًا.',
    'password' => 'كلمة المرور غير صحيحة.',
    'present' => 'يجب تقديم حقل :attribute.',
    'regex' => 'صيغة حقل :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب في حال ما إذا كان :other يساوي :value.',
    'required_unless' => 'حقل :attribute مطلوب في حال ما لم يكن :other يساوي :values.',
    'required_with' => 'حقل :attribute مطلوب إذا توفّر :values.',
    'required_with_all' => 'حقل :attribute مطلوب إذا توفّر :values.',
    'required_without' => 'حقل :attribute مطلوب إذا لم يتوفّر :values.',
    'required_without_all' => 'حقل :attribute مطلوب إذا لم يتوفّر :values.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'same' => 'يجب أن يتطابق حقل :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :size عنصرٍ/عناصر بالضبط.',
        'file' => 'يجب أن يكون حجم ملف حقل :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute مساوية لـ :size.',
        'string' => 'يجب أن يحتوي نص حقل :attribute على :size حروفٍ/حرفًا بالضبط.',
    ],
    'starts_with' => 'يجب أن يبدأ حقل :attribute بأحد القيم التالية: :values',
    'string' => 'يجب أن يكون حقل :attribute نصًا.',
    'timezone' => 'يجب أن يكون حقل :attribute نطاقًا زمنيًا صحيحًا.',
    'unique' => 'قيمة حقل :attribute مُستخدمة من قبل.',
    'uploaded' => 'فشل في تحميل الـ :attribute.',
    'url' => 'صيغة رابط حقل :attribute غير صحيحة.',
    'uuid' => 'حقل :attribute يجب أن يكون بصيغة UUID سليمة.',
    'birth_date' => 'يرجى تحديد تاريخ قبل تاريخ اليوم',
    'captcha' => 'فشل التحقق من CAPTCHA. يرجى المحاولة مرة أخرى.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'image' => [
            'width' => 'يجب أن يكون عرض الصورة مساوي ل :val px',
            'height' => 'يجب أن يكون ارتفاع الصورة مساوي ل :val px',
            'min_width' => 'يجب عرض الصورة على الأقل أن يكون :val px',
            'max_width' => 'يجب عرض الصورة أن لا يكون أكبر من :val px',
            'min_height' => 'يجب ارتفاع الصورة على الأقل أن يكون :val px',
            'max_height' => 'يجب ارتفاع الصورة أن لا يكون أكبر من :val px',
        ],

        'required-fields' => 'املأ الحقول المطلوبة اولاً',
        'adult' => 'يجب أن يكون العمر على الأقل 16 عامًا.',
        'password_rule' => 'يجب أن تكون كلمة المرور مكونة من 8 أحرف على الأقل وتحتوي على حروف ,أرقام و محارف خاصة.',
        'phone_rule' => 'يجب أن يحتوي رقم الهاتف على أرقام وعلامات + ( ) - فقط.',
        'edit_expiry_date' => 'يجب أن يكون تاريخ الانتهاء مساوياً أو أكبر من تاريخ الانتهاء القديم.'

    ],


    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'username' => 'اسم المستخدم',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'phone' => 'الموبايل',
        'gender' => 'الجنس',
        'birth_date' => 'تاريخ الميلاد',
        'role' => 'الدور الوظيفي',
        'city_id' => 'اسم المدينة',
        'location' => 'الموقع',
        'message' => 'الرسالة',
        'editing' => [
            'weekly_hours' => 'ساعات العمل الأسبوعية',
            'type' => [
                'ar' => 'النوع (عربي)',
                'en' => 'النوع (انكليزي)'
            ]
        ],

        'g-recaptcha-response' => 'captcha (I\'m not a robot)',

    ],


    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */


];


