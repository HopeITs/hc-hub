<?php

/*
 * نصوص الفريق 2 — المرحلة الثانية (نموذج التقديم التجريبي، دعوات الحملة).
 * منفصلة عن recruitment.php الذي يعدّله الفريق 1 باستمرار: عندما أضاف
 * الفريقان إلى ذلك الملف أسقط الدمج كل هذه المفاتيح.
 */
return [
    'invalid_or_expired_link' => 'رابط التقديم هذا غير صالح أو منتهي الصلاحية.',
    'campaign_not_accepting' => 'هذه الحملة لا تستقبل طلبات تقديم حالياً.',
    'invitation_required' => 'هذه الحملة مخصصة للمدعوين فقط، ولم يتم العثور على هذا البريد الإلكتروني ضمن قائمة الدعوات.',
    'already_applied' => 'يوجد طلب تقديم مسبق لهذا البريد الإلكتروني على هذه الحملة.',
    'missing_required_field' => 'حقل :field مطلوب.',
    'file_requires_real_form' => 'الحقل ":field" يتطلب رفع ملف، ونموذج الاختبار هذا لا يدعم رفع الملفات. استخدم رابط التقديم الحقيقي (/careers/{token}).',

    'public_apply_title' => 'التقديم على هذه الوظيفة',
    'public_apply_full_name' => 'الاسم الكامل',
    'public_apply_email' => 'البريد الإلكتروني',
    'public_apply_phone' => 'رقم الهاتف',
    'public_apply_city' => 'المدينة',
    'public_apply_privacy_consent' => 'أوافق على استخدام بياناتي لمعالجة هذا الطلب.',
    'public_apply_submit' => 'إرسال الطلب',
    'public_apply_thanks_title' => 'تم استلام طلبك',
    'public_apply_thanks_body' => 'شكراً لتقديمك. سيقوم فريق التوظيف بمراجعة طلبك والتواصل معك في حال تم ترشيحك.',

    'invites_title' => 'دعوات الحملة',
    'invites_description' => 'فقط الأشخاص المضافون هنا يمكنهم إرسال طلب تقديم عندما تكون هذه الحملة مخصصة للمدعوين فقط.',
    'invite_full_name' => 'الاسم الكامل',
    'invite_email' => 'البريد الإلكتروني',
    'invite_add' => 'إضافة دعوة',
    'invite_added' => 'تم حفظ الدعوة.',
    'invite_status' => 'الحالة',
    'invite_used_at' => 'تاريخ التقديم',
    'invite_cancel' => 'إلغاء',
    'invite_cancel_confirm' => 'إلغاء هذه الدعوة؟ لن يتمكن الشخص من التقديم بعد الآن.',
    'invite_cancelled' => 'تم إلغاء الدعوة.',
    'invite_status_pending' => 'قيد الانتظار',
    'invite_status_sent' => 'أُرسلت',
    'invite_status_used' => 'تم التقديم',
    'invite_status_cancelled' => 'ملغاة',
    'invite_already_exists' => 'هذا البريد الإلكتروني مدعو مسبقاً لهذه الحملة.',
    'invite_already_used' => 'هذا الشخص قدّم طلباً مسبقاً باستخدام هذه الدعوة.',
    'invite_not_invitation_only' => 'الدعوات تخص الحملات المخصصة للمدعوين فقط.',
];
