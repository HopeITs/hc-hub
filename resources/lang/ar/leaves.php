<?php

return [

    // حالة طلب الإجازة: مصطلح واحد لكل حالة في كل الواجهات.
    'status' => [
        'pending' => 'قيد المراجعة',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
    ],

    'unit' => [
        'legend' => 'وحدة الإجازة',
        'day' => 'يوم',
        'hour' => 'ساعة',
    ],

    'gender' => [
        'any' => 'الكل',
        'male' => 'ذكر',
        'female' => 'أنثى',
    ],

    'marital' => [
        'any' => 'الكل',
        'single' => 'أعزب',
        'married' => 'متزوج',
    ],

    'select-employee' => 'اختر الموظف…',

    // في الاتجاه من اليمين إلى اليسار يشير السهم إلى نهاية المدة.
    'range-separator' => '←',

    // صيغ الجمع الست: صفر | واحد | اثنان | 3–10 | 11–99 | 100 فأكثر.
    'duration' => [
        'days' => 'لا أيام|يوم واحد|يومان|:count أيام|:count يومًا|:count يوم',
        'days-fraction' => ':count يوم',
        'hours' => 'لا ساعات|ساعة واحدة|ساعتان|:count ساعات|:count ساعة|:count ساعة',
        'minutes' => 'لا دقائق|دقيقة واحدة|دقيقتان|:count دقائق|:count دقيقة|:count دقيقة',
        'and' => ':first و:second',
    ],

    'validation' => [
        'start-hour-within-working-hours' => 'يجب أن يكون وقت البدء ضمن ساعات دوام المركز.',
        'end-hour-within-working-hours' => 'يجب أن يكون وقت الانتهاء ضمن ساعات دوام المركز.',
        'end-hour-after-start-hour' => 'يجب أن يكون وقت الانتهاء بعد وقت البدء.',
        'max-hours' => 'يجب ألا تتجاوز المدة :max.',
        'max-days' => 'إجمالي مدة الإجازة (:days) يتجاوز الحد المسموح به (:max).',
    ],

];
