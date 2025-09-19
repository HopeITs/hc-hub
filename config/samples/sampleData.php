<?php

use App\Enums\ContractPlace;
use App\Enums\ContractType;
use App\Enums\Gender;
use App\Enums\LeaveType;
use App\Enums\MaritalStatus;

return [
    'cities' => [
        ['slug' => 'SYR', 'name' => ['en' => 'Syria', 'ar' => 'سوريا'], 'is_active' => true, 'code' => '000'],
        ['slug' => 'ALP', 'name' => ['en' => 'Aleppo', 'ar' => 'حلب'], 'is_active' => true, 'code' => '001'],
        ['slug' => 'HMS', 'name' => ['en' => 'Homs', 'ar' => 'حمص'], 'is_active' => true, 'code' => '002'],
        ['slug' => 'DMS', 'name' => ['en' => 'Damascus', 'ar' => 'دمشق'], 'is_active' => true, 'code' => '003'],
        ['slug' => 'LAT', 'name' => ['en' => 'Latakia', 'ar' => 'اللاذقية'], 'is_active' => true, 'code' => '004'],
        ['slug' => 'TAR', 'name' => ['en' => 'Tartus', 'ar' => 'طرطوس'], 'is_active' => true, 'code' => '005'],
        ['slug' => 'HAM', 'name' => ['en' => 'Hama', 'ar' => 'حماة'], 'is_active' => false, 'code' => '006'],
        ['slug' => 'DAR', 'name' => ['en' => 'Daraa', 'ar' => 'درعا'], 'is_active' => false, 'code' => '007'],
        ['slug' => 'SWD', 'name' => ['en' => 'Suwaida', 'ar' => 'السويداء'], 'is_active' => false, 'code' => '008'],
        ['slug' => 'QNT', 'name' => ['en' => 'Qounaitra', 'ar' => 'القنيطرة'], 'is_active' => false, 'code' => '009'],
        ['slug' => 'RQA', 'name' => ['en' => ' Ar-raqqa', 'ar' => 'الرقة'], 'is_active' => false, 'code' => '010'],
        ['slug' => 'DZR', 'name' => ['en' => ' Deir Az-zor', 'ar' => 'دير الزور'], 'is_active' => false, 'code' => '011'],
        ['slug' => 'HSK', 'name' => ['en' => ' Al-Hasakah', 'ar' => 'الحسكة'], 'is_active' => false, 'code' => '012'],
        ['slug' => 'IDL', 'name' => ['en' => 'Idleb', 'ar' => 'إدلب'], 'is_active' => false, 'code' => '013'],
        ['slug' => 'RID', 'name' => ['en' => 'Rif Dimashq', 'ar' => 'ريف دمشق'], 'is_active' => false, 'code' => '014'],
    ],

    'ContractType' => [
        [
            'name' => ['en' => 'Full Annual', 'ar' => 'سنوي كامل'],
            'place' => ContractPlace::Onsite,
            'type' => ContractType::FullTime,
        ],
        [
            'name' => ['en' => 'Partial Annual', 'ar' => 'سنوي جزئي'],
            'place' => ContractPlace::Onsite,
            'type' => ContractType::PartTime,
        ],
        [
            'name' => ['en' => 'Trial Full', 'ar' => 'تجريبي كامل'],
            'place' => ContractPlace::Onsite,
            'type' => ContractType::FullTime,
        ],
        [
            'name' => ['en' => 'Trial Partial', 'ar' => 'تجريبي جزئي'],
            'place' => ContractPlace::Onsite,
            'type' => ContractType::PartTime,
        ],
        [
            'name' => ['en' => 'Online Agreement Contract', 'ar' => 'عقد اتفاق اونلاين'],
            'place' => ContractPlace::Online,
            'type' => ContractType::Freelance,
        ],
        [
            'name' => ['en' => 'Onsite Agreement Contract', 'ar' => 'عقد اتفاق في الموقع'],
            'place' => ContractPlace::Onsite,
            'type' => ContractType::Freelance,
        ],
    ],

    'departments' => [
        ['name' => 'Management', 'description' => null, 'email' => 'headoffice@hcsyria.org'],
        ['name' => 'Front Desk', 'description' => null, 'email' => null],
        ['name' => 'Teachers', 'description' => null, 'email' => null],
        ['name' => 'Trainers/ Coach', 'description' => null, 'email' => null],
        ['name' => 'IT', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو تطوير التطبيقات البرمجية من أجل حفظ وعرض بيانات المؤسسة وإنشاء تقارير وإحصائيات ومتابعة صيانة الأمور الالكترونية وملحقاتها في المراكز.', 'email' => 'it@hcsyria.org'],
        ['name' => 'Communication', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو العمل على توثيق النشاطات التي تقوم بها البرامج التابعة لـ Christian Hope Center أو يشرف عليها أو يكون شريكاً في إدارتها أو تنفيذها، وذلك من خلال التصوير التوثيقي للصور والفيديو، باستخدام كافة الكاميرات والمعدات التي يؤمنها له المركز.', 'email' => 'ct@hcsyria.org'],
        ['name' => 'HR', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو التواصل مع مدراء المشاريع في المحافظة ومساعدتهم في جميع المسائل المتعلقة بالموارد البشرية.', 'email' => null],
        ['name' => 'Finance', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو إدارة الشؤون المالية لمشاريع مؤسسة "Christian Hope Center “في سوريا، بما في ذلك إعداد الميزانيات والتقارير المالية، ومتابعة الحسابات، وضمان الشفافية والكفاءة في العمليات المالية', 'email' => 'finance@hcsyria.org'],
        ['name' => 'Meal', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو متابعة وتقييم سير آلية المشاريع التابعة لـ Christian Hope Center في سوريا وضمان فعاليتها وكفاءتها واستدامتها بحيث تكون مطابقة للـ proposals المُرسلة للممولين.', 'email' => null],
        ['name' => 'Fundraising', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو التواصل مع الجهات المانحة ومتابعة المشاريع الممولة في سوريا بعد التنسيق مع الإدارة العامة.', 'email' => 'pr@hcsyria.org'],
        ['name' => 'Logistic', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو شراء وتسليم جميع مستلزمات المشاريع الصغيرة للمستفيدين ومشتريات البرامج التابعة لـ Hope Center ومتابعة صيانة المراكز.', 'email' => null],
        ['name' => 'Legal Affairs', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو تنسيق ومتابعة جميع الأمور القانونية التابعة لبرامج Christian Hope Center', 'email' => null],
        ['name' => 'Services/ Keepers', 'description' => 'الهدف الرئيسي من هذا العمل التطوعي هو استقبال المستفيدين وتوجيههم والاهتمام بنظافة المكان بشكل عام.', 'email' => null],
    ],

    'programs' => [
        [
            'name' => 'Hope Center',
            'description' => [
                'en' => 'An ecumenical Christian organization established in 2018 under the guidance of the Latin Church in Syria. It empowers Syrian families through diverse initiatives across relief, livelihood, development, and education.',
                'ar' => 'مركز الرجاء المسيحي منظمة مسكونية، تأسست مطلع عام ٢٠١٨، وتعمل بتوجيه من الكنيسة اللاتينية في سورية. رسالتنا هي تمكين الأسر السورية من خلال مبادرات متعددة تستهدف كل مكون من مكونات الأسرة، في مجالات الإغاثة، ودعم سبل العيش، والتنمية، والتعليم.',
            ],
            'slug' => 'HC',
            'code' => '001',
            'is_active' => true,
        ],
        [
            'name' => 'Micro Project',
            'description' => [
                'en' => 'Aimed at empowering Syrian families via livelihood, education, and development programs.',
                'ar' => 'مركز الرجاء المسيحي منظمة مسكونية، تأسست مطلع عام ٢٠١٨، وتعمل بتوجيه من الكنيسة اللاتينية في سورية. رسالتنا هي تمكين الأسر السورية من خلال مبادرات متعددة تستهدف كل مكون من مكونات الأسرة، في مجالات الإغاثة، ودعم سبل العيش، والتنمية، والتعليم.',
            ],
            'slug' => 'MP',
            'code' => '002',
            'is_active' => true,
        ],
        [
            'name' => 'Relief Programs',
            'description' => [
                'en' => 'Aims to reduce suffering caused by war and economic crises through essential humanitarian support.',
                'ar' => 'الهدف من البرامج الإغاثية هو تخفيف المعاناة الناجمة عن الحرب السورية وتداعياتها الاقتصادية المعقدة، والتي تُثقل كاهل غالبية الأسر التي تعيش تحت خط الفقر. هدفنا هو مساعدة هذه الأسرعلى تلبية احتياجاتها الأساسية والحفاظ على كرامتها.',
            ],
            'slug' => 'RLF',
            'code' => '003',
            'is_active' => true,
        ],
        [
            'name' => 'Family to Family (Rodzina Rodzinie)',
            'description' => [
                'en' => 'Aims to reduce suffering caused by war and economic crises through essential humanitarian support.',
                'ar' => 'الهدف من البرامج الإغاثية هو تخفيف المعاناة الناجمة عن الحرب السورية وتداعياتها الاقتصادية المعقدة، والتي تُثقل كاهل غالبية الأسر التي تعيش تحت خط الفقر. هدفنا هو مساعدة هذه الأسرعلى تلبية احتياجاتها الأساسية والحفاظ على كرامتها.',
            ],
            'slug' => 'F2F',
            'code' => '003',
            'is_active' => true,
        ],
        [
            'name' => 'Assistance for Earthquake',
            'description' => [
                'en' => 'Supports post-war recovery through rebuilding infrastructure, schools, homes, and implementing sustainable energy solutions.',
                'ar' => 'يُعدّ عمل إعادة التأهيل في فترة ما بعد الحرب أمرًا بالغ الأهمية للتعافي المبكر للمجتمعات. كما أنه ضروري لاستعادة البنى التحتية المتضررة وخدمات الدعم التي تُمكّن الناس من استئناف حياتهم. وقد ساهم مركز الأمل المسيحي في هذا المجال من خلال إعادة تأهيل المرافق التعليمية والمجتمعية المدمرة، مثل المدارس والملاعب، وترميم المنازل وأماكن العمل. كما امتدت جهودنا إلى دعم المجتمعات الريفية من خلال توفير البنية التحتية الكهروضوئية لإنتاج الكهرباء واستخراج المياه في عدة قرى.',
            ],
            'slug' => 'MCC',
            'code' => '004',
            'is_active' => false,
        ],
        [
            'name' => 'School to School (Szkoła szkole)',
            'description' => [
                'en' => 'Supports post-war recovery through rebuilding infrastructure, schools, homes, and implementing sustainable energy solutions.',
                'ar' => 'يُعدّ عمل إعادة التأهيل في فترة ما بعد الحرب أمرًا بالغ الأهمية للتعافي المبكر للمجتمعات. كما أنه ضروري لاستعادة البنى التحتية المتضررة وخدمات الدعم التي تُمكّن الناس من استئناف حياتهم. وقد ساهم مركز الأمل المسيحي في هذا المجال من خلال إعادة تأهيل المرافق التعليمية والمجتمعية المدمرة، مثل المدارس والملاعب، وترميم المنازل وأماكن العمل. كما امتدت جهودنا إلى دعم المجتمعات الريفية من خلال توفير البنية التحتية الكهروضوئية لإنتاج الكهرباء واستخراج المياه في عدة قرى.',
            ],
            'slug' => 'S2S',
            'code' => '004',
            'is_active' => false,
        ],
        [
            'name' => 'Development Impact and Support Center',
            'description' => [
                'en' => 'Supports fresh graduates in developing foundational, employability, and peer mentorship skills to enter the job market.',
                'ar' => 'الهدف من برنامج DISC في مساعدة الخريجين الجدد على توجيه أنفسهم نحو العمل وتزويدهم بالمهارات اللازمة لضمان استعدادهم لدخول سوق العمل. يعمل المشروع على ثلاثة جوانب أساسية في آنٍ واحد: المهارات الأساسية، ومهارات التوظيف، والرفقة لتبادل الخبرات والدعم.',
            ],
            'slug' => 'DISC',
            'code' => '005',
            'is_active' => true,
        ],
        [
            'name' => 'Study Zone',
            'description' => [
                'en' => null,
                'ar' => null,
            ],
            'slug' => 'SZ',
            'code' => '006',
            'is_active' => true,
        ],
        [
            'name' => 'Homework Room',
            'description' => [
                'en' => null,
                'ar' => null,
            ],
            'slug' => 'HWR',
            'code' => '007',
            'is_active' => true,
        ],
        [
            'name' => 'Children University',
            'description' => [
                'en' => 'An interactive educational program for children aged 6 to 16, designed to simulate university-style learning through experience.',
                'ar' => 'جامعة الأطفال برنامج تعليمي مصمم خصيصًا للأطفال من سن 6 إلى 16 عامًا، يشبه المقررات الجامعية، بأسلوب تفاعلي متقدم ومبتكر، يركز على التجربة كأداة تعليمية، بالإضافة إلى أساليب متنوعة أخرى. يتكون البرنامج من مرحلتين: مستوى أساسي ومستوى متقدم، مدة كل مرحلة 4 أشهر، ويتكون كل مستوى من يومين دراسيين ويوم واحد للأنشطة أسبوعيًا.',
            ],
            'slug' => 'CU',
            'code' => '008',
            'is_active' => true,
        ],
        [
            'name' => 'Sha8er',
            'description' => [
                'en' => null,
                'ar' => null,
            ],
            'slug' => 'Sha8er',
            'code' => '009',
            'is_active' => true,
        ],
        [
            'name' => 'Entrepreneurship project',
            'description' => [
                'en' => 'Leadership program to equip community leaders with technical and leadership skills for effective crisis response.',
                'ar' => 'نظرًا لعدم وجود قادة ناجحين يمكنهم توجيه المجتمعات المحلية خلال أوقات الأزمات، تم إنشاء برنامج القيادة لاستهداف الأعضاء النشطين في المجتمع وتزويدهم بالمهارات الفنية والقدرات القيادية، والتي ستدعمهم في التقييم الدقيق لاحتياجات مجتمعاتهم الحالية والمستقبلية واقتراح حلول فعالة، وبالتالي المساهمة الفعلية على تشجيع تنمية المجتمع.',
            ],
            'slug' => 'ESP',
            'code' => '009',
            'is_active' => true,
        ],
        [
            'name' => 'Leadership Empowerment & Achievement Program',
            'description' => [
                'en' => 'Leadership program to equip community leaders with technical and leadership skills for effective crisis response.',
                'ar' => 'نظرًا لعدم وجود قادة ناجحين يمكنهم توجيه المجتمعات المحلية خلال أوقات الأزمات، تم إنشاء برنامج القيادة لاستهداف الأعضاء النشطين في المجتمع وتزويدهم بالمهارات الفنية والقدرات القيادية، والتي ستدعمهم في التقييم الدقيق لاحتياجات مجتمعاتهم الحالية والمستقبلية واقتراح حلول فعالة، وبالتالي المساهمة الفعلية على تشجيع تنمية المجتمع.',
            ],
            'slug' => 'LEAP',
            'code' => '010',
            'is_active' => true,
        ],
    ],

    'centers' => [
        ['name' => 'Hope Center/Head Office', 'city_id' => 2, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => 'Hope Center', 'city_id' => 4, 'address' => 'Damascus', 'is_active' => true],
        ['name' => 'Micro Project', 'city_id' => 3, 'address' => 'Homs', 'is_active' => true],
        ['name' => 'Micro Project', 'city_id' => 4, 'address' => 'Damascus', 'is_active' => true],
        ['name' => 'Micro Project', 'city_id' => 5, 'address' => 'Latakia', 'is_active' => true],
        ['name' => 'Children University', 'city_id' => 2, 'address' => 'Homs', 'is_active' => true],
        ['name' => 'Children University', 'city_id' => 3, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => 'ADISC', 'city_id' => 2, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => 'HDISC', 'city_id' => 3, 'address' => 'Homs', 'is_active' => true],
        ['name' => 'DDISC', 'city_id' => 4, 'address' => 'Damascus', 'is_active' => true],
        ['name' => 'LDISC', 'city_id' => 5, 'address' => 'Latakia', 'is_active' => false],
        ['name' => 'Science Zone', 'city_id' => 2, 'address' => 'Homs', 'is_active' => true],
        ['name' => 'Science Zone', 'city_id' => 3, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => 'Study Zone Al-Nouzha', 'city_id' => 3, 'address' => 'Al-Nouzha', 'is_active' => true],
        ['name' => 'Study Zone Al-Mashta', 'city_id' => 3, 'address' => 'Al-Mashta', 'is_active' => true],
        ['name' => 'Study Zone Armenian', 'city_id' => 3, 'address' => 'Armenian', 'is_active' => true],
        ['name' => 'Study Zone Al-Muzayneh', 'city_id' => 3, 'address' => 'Al-Muzayneh', 'is_active' => true],
        ['name' => 'Study Zone Bab-Sharqi', 'city_id' => 4, 'address' => 'Bab-Sharqi', 'is_active' => true],
        ['name' => 'Study Zone Bab-Touma', 'city_id' => 4, 'address' => 'Bab-Touma', 'is_active' => true],
        ['name' => 'Study Zone Ziraa 1', 'city_id' => 5, 'address' => 'Ziraa 1', 'is_active' => true],
        ['name' => 'Study Zone Ziraa 2', 'city_id' => 5, 'address' => 'Ziraa 2', 'is_active' => true],
        ['name' => 'Study Zone Al-Haram', 'city_id' => 5, 'address' => 'Al-Haram', 'is_active' => true],
        ['name' => 'Study Zone Tartous', 'city_id' => 6, 'address' => 'Tartous', 'is_active' => true],
        ['name' => 'LEAP Aleppo', 'city_id' => 2, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => 'LEAP Homs', 'city_id' => 3, 'address' => 'Homs', 'is_active' => true],

    ],

    'checkListsItems' => [['name' => 'ID Cartoon', 'order' => 1], ['name' => 'Email', 'order' => 1], ['name' => 'Email Signature', 'order' => 1], ['name' => 'Laptop', 'order' => 1], ['name' => 'Mobile', 'order' => 1], ['name' => 'SIM', 'order' => 1], ['name' => 'Mug', 'order' => 2], ['name' => 'ID Plastic', 'order' => 2], ['name' => 'Web photo', 'order' => 2], ['name' => 'Recieve Electronics', 'order' => 3], ['name' => 'Cancel work mail & work accounts', 'order' => 3], ['name' => 'Delete the volunteer from the web page', 'order' => 3]],

    'leaveTypes' => [
        [
            'name' => ['en' => 'Annual', 'ar' => 'سنوية'],
            'base_days' => 12,
            'allowed_gender' => LeaveType::GENDER_ANY,
            'marital_status' => LeaveType::MARITAL_STATUS_ANY,
        ],
        [
            'name' => ['en' => 'Full Sick Leave', 'ar' => 'مرضية كاملة'],
            'base_days' => 12,
            'allowed_gender' => LeaveType::GENDER_ANY,
            'marital_status' => LeaveType::MARITAL_STATUS_ANY,
        ],
        [
            'name' => ['en' => 'Partial Sick Leave', 'ar' => 'مرضية جزئية'],
            'base_days' => 12,
            'allowed_gender' => LeaveType::GENDER_ANY,
            'marital_status' => LeaveType::MARITAL_STATUS_ANY,
        ],
        [
            'name' => ['en' => 'Maternity 1', 'ar' => 'إجازة أمومة 1'],
            'base_days' => 120,
            'allowed_gender' => LeaveType::GENDER_FEMALE,
            'marital_status' => LeaveType::MARITAL_STATUS_MARRIED,
        ],
        [
            'name' => ['en' => 'Maternity 2', 'ar' => 'إجازة أمومة 2'],
            'base_days' => 60,
            'allowed_gender' => LeaveType::GENDER_FEMALE,
            'marital_status' => LeaveType::MARITAL_STATUS_MARRIED,
        ],
        [
            'name' => ['en' => 'Paternity', 'ar' => 'إجازة أبوة'],
            'base_days' => 15,
            'allowed_gender' => LeaveType::GENDER_MALE,
            'marital_status' => LeaveType::MARITAL_STATUS_MARRIED,
        ],
        [
            'name' => ['en' => 'Marriage', 'ar' => 'زواج'],
            'base_days' => 7,
            'allowed_gender' => LeaveType::GENDER_ANY,
            'marital_status' => LeaveType::MARITAL_STATUS_SINGLE,
        ],
        [
            'name' => ['en' => 'Death Degree 1', 'ar' => 'وفاة درجة 1'],
            'base_days' => 7,
            'allowed_gender' => LeaveType::GENDER_ANY,
            'marital_status' => LeaveType::MARITAL_STATUS_ANY,
        ],
        [
            'name' => ['en' => 'Death Degree 2', 'ar' => 'وفاة درجة 2'],
            'base_days' => 1,
            'allowed_gender' => LeaveType::GENDER_ANY,
            'marital_status' => LeaveType::MARITAL_STATUS_ANY,
        ],
        [
            'name' => ['en' => 'Unpaid', 'ar' => ' غير مدفوعة'],
            'base_days' => 12,
            'allowed_gender' => LeaveType::GENDER_ANY,
            'marital_status' => LeaveType::MARITAL_STATUS_ANY,
        ],
    ],

    'annualLeaveRules' => [
        [
            'years_of_volunteering' => 1,
            'day_per_month' => 1,
            'days' => 12,
        ],
        [
            'years_of_volunteering' => 2,
            'day_per_month' => 1.25,
            'days' => 14,
        ],
        [
            'years_of_volunteering' => 3,
            'day_per_month' => 1.5,
            'days' => 18,
        ],
        [
            'years_of_volunteering' => 5,
            'day_per_month' => 1.6,
            'days' => 20,
        ],
        [
            'years_of_volunteering' => 6,
            'day_per_month' => 1.8,
            'days' => 22,
        ],
    ],
    'jobLevels' => [
        ['name' => ['en' => 'Program Manager', 'ar' => 'مدير البرنامج'], 'description' => null, 'grade' => 1, 'email' => 'managers@hcsyria.org'],
        ['name' => ['en' => 'Project Manager', 'ar' => 'مدير المشروع'], 'description' => null, 'grade' => 2, 'email' => 'managers@hcsyria.org'],
        ['name' => ['en' => 'Department Manager', 'ar' => 'مدير القسم'], 'description' => null, 'grade' => 3, 'email' => 'managers@hcsyria.org'],
        ['name' => ['en' => 'Team', 'ar' => 'الفريق'], 'description' => null, 'grade' => 4, 'email' => 'team@hcsyria.org'],
        ['name' => ['en' => 'Service Agreement', 'ar' => 'اتفاقية الخدمة'], 'description' => null, 'grade' => 5, 'email' => null],
        ['name' => ['en' => 'Keepers', 'ar' => 'المستخدمون'], 'description' => null, 'grade' => 6, 'email' => null]
    ],

    'employees' => [
        [
            'name' => ['en' => 'George', 'ar' => 'جورج'],
            'years_of_service' => 1,
            'username' => 'george.s',
            'direct_manager_id' => null,
            'tech_manager_id' => null,
            'national_number' => '11021034567',
            'dob' => '1985-04-12',
            'actual_dob' => '1985-04-12',
            'civil_registry_office' => 'Damascus',
            'civil_registry_number' => '123A',
            'place_of_registration' => 'Damascus',
            'gender' => 1,
            'father_name' => ['en' => 'Doe', 'ar' => 'دو'],
            'mother_name' => ['en' => 'Doe', 'ar' => 'دو'],
            'nationality' => 1,
            'marital_status' => 1,
            'children' => 0,
            'work_phone' => '0988123456',
            'personal_phone' => '0933123456',
            'personal_email' => 'exxx@example.com',
            'address' => ['en' => 'Aleppo', 'ar' => 'حلب'],
            'skills' => 'etc1, etc2, etc3',
            'interests' => 'Reading, Technology',
            'is_active' => true,
        ],
    ],
    'positions' => [

        // -------------------------------
        // Aleppo (city_id = 2)
        // -------------------------------
        ['title' => 'Chief Executive Officer', 'title_ar' => 'الرئيس التنفيذي', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 1, 'city_id' => 1,'center_id' => 1,],
        ['title' => 'Deputy Chief Executive Officer', 'title_ar' => 'نائب الرئيس التنفيذي', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 1, 'city_id' => 1,'center_id' => 1,],
        ['title' => 'Second Deputy Chief Executive Officer', 'title_ar' => 'نائب ثاني للرئيس التنفيذي', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 1, 'city_id' => 1,'center_id' => 1,],
        ['title' => 'IT Manager', 'title_ar' => 'مدير تكنولوجيا المعلومات', 'program_id' => 1, 'department_id' => 5, 'job_level_id' => 3, 'city_id' => 1,'center_id' => 1,],
        ['title' => 'IT Specialist', 'title_ar' => 'متخصص تكنولوجيا المعلومات', 'program_id' => 1, 'department_id' => 5, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'HR Generalist', 'title_ar' => 'مسؤول الموارد البشرية', 'program_id' => 1, 'department_id' => 7, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Legal Affairs Consultant', 'title_ar' => 'مستشار الشؤون القانونية', 'program_id' => 1, 'department_id' => 12, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Senior Financial Manager', 'title_ar' => 'المدير المالي الأول', 'program_id' => 1, 'department_id' => 8, 'job_level_id' => 3, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Accountant', 'title_ar' => 'محاسب', 'program_id' => 1, 'department_id' => 8, 'job_level_id' => 4, 'city_id' => 2, 'center_id' => 1,],
        ['title' => 'Program Manager', 'title_ar' => 'مدير البرنامج', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 1, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Projects and Fundraising Manager', 'title_ar' => 'مدير المشاريع وجمع التبرعات', 'program_id' => 1, 'department_id' => 10, 'job_level_id' => 3, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Communication Manager / Syria', 'title_ar' => 'مدير التواصل / سوريا', 'program_id' => 1, 'department_id' => 6, 'job_level_id' => 3, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Communication Officer / Videographer', 'title_ar' => 'مسؤول تواصل / مصور', 'program_id' => 1, 'department_id' => 6, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Communication Officer / Designer', 'title_ar' => 'مسؤول تواصل / مصمم', 'program_id' => 1, 'department_id' => 6, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Communication Officer / Photographer', 'title_ar' => 'مسؤول تواصل / مصور فوتوغرافي', 'program_id' => 1, 'department_id' => 6, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Public Relations Officer', 'title_ar' => 'مسؤول علاقات عامة', 'program_id' => 1, 'department_id' => 6, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'Administrator', 'title_ar' => 'إداري', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 4, 'city_id' => 2, 'center_id' => 1,],
        ['title' => 'Project Officer', 'title_ar' => 'مسؤول مشروع', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 4, 'city_id' => 2, 'center_id' => 1,],
        ['title' => 'Relief Manager', 'title_ar' => 'مدير الإغاثة', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 3, 'city_id' => 2,'center_id' => 1,],
        ['title' => 'MEAL Manager', 'title_ar' => 'مسؤول مراقبة وتقييم', 'program_id' => 1, 'department_id' => 9, 'job_level_id' => 3, 'city_id' => 2, 'center_id' => 1,],
        ['title' => 'Logistic Officer', 'title_ar' => 'مسؤول المشتريات', 'program_id' => 1, 'department_id' => 11, 'job_level_id' => 4, 'city_id' => 2, 'center_id' => 1,],
        ['title' => 'Field Officer Assistant', 'title_ar' => 'مساعد مسؤول ميداني', 'program_id' => 1, 'department_id' => 11, 'job_level_id' => 4, 'city_id' => 2, 'center_id' => 1,],
        ['title' => 'Spiritual Care Specialist', 'title_ar' => 'مختص رعاية روحية', 'program_id' => 1, 'department_id' => 1, 'job_level_id' => 4, 'city_id' => 2,'center_id' => 1,],

        // -------------------------------
        // Damascus (city_id = 4)
        // -------------------------------
        ['title' => 'Project Assistant Manager', 'title_ar' => 'مساعد مدير المشروع', 'program_id' => 7, 'department_id' => 1, 'job_level_id' => 2, 'city_id' => 4,'center_id' => 8,],
        ['title' => 'Project Manager', 'title_ar' => 'مدير مشروع', 'program_id' => 7, 'department_id' => 1, 'job_level_id' => 2, 'city_id' => 4,'center_id' => 8,],
        ['title' => 'Supervisor', 'title_ar' => 'مشرف', 'program_id' => 7, 'department_id' => 1, 'job_level_id' => 3, 'city_id' => 4,'center_id' => 8,],
        ['title' => 'Center Keeper', 'title_ar' => 'مدبرة مركز', 'program_id' => 7, 'department_id' => 13, 'job_level_id' => 6, 'city_id' => 4,'center_id' => 8,],
        ['title' => 'English Teacher', 'title_ar' => 'استاذ لغة انجليزية', 'program_id' => 7, 'department_id' => 3, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 8,],
        ['title' => 'ICDL Trainer', 'title_ar' => 'مدرب ICDL', 'program_id' => 7, 'department_id' => 4, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 8,],
        ['title' => 'Companion', 'title_ar' => 'مرافق', 'program_id' => 7, 'department_id' => 1, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 8, ],

        ['title' => 'Project Assistant Manager', 'title_ar' => 'مساعد مدير المشروع', 'program_id' => 8, 'department_id' => 1, 'job_level_id' => 2, 'city_id' => 4,'center_id' => 24,],
        ['title' => 'Project Manager', 'title_ar' => 'مدير مشروع', 'program_id' => 8, 'department_id' => 1, 'job_level_id' => 2, 'city_id' => 4,'center_id' => 24,],
        ['title' => 'Project Team Member', 'title_ar' => 'عضو في فريق البرنامج', 'program_id' => 8, 'department_id' => 1, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 24,],
        ['title' => 'Center Keeper', 'title_ar' => 'مدبرة مركز', 'program_id' => 8, 'department_id' => 13, 'job_level_id' => 6, 'city_id' => 4,'center_id' => 24,],

        ['title' => 'Project Manager', 'title_ar' => 'مدير مشروع', 'program_id' => 2, 'department_id' => 1, 'job_level_id' => 2, 'city_id' => 4,'center_id' => 4,], // Micro Project
        ['title' => 'Field Officer', 'title_ar' => 'مسؤول ميداني', 'program_id' => 2, 'department_id' => 11, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 4,],
        ['title' => 'Field Officer Assistant', 'title_ar' => 'مساعد مسؤول ميداني', 'program_id' => 2, 'department_id' => 11, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 4,],
        ['title' => 'Center Keeper', 'title_ar' => 'مدبرة مركز', 'program_id' => 2, 'department_id' => 13, 'job_level_id' => 6, 'city_id' => 4,'center_id' => 4,],

        ['title' => 'Design Manager', 'title_ar' => 'مدير التصميم', 'program_id' => 1, 'department_id' => 5, 'job_level_id' => 3, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'Legal Affairs Consultant', 'title_ar' => 'مستشار الشؤون القانوينة', 'program_id' => 1, 'department_id' => 12, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'HR Manager', 'title_ar' => 'مسؤول الموارد البشرية', 'program_id' => 1, 'department_id' => 7, 'job_level_id' => 3, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'Logistic Officer', 'title_ar' => 'مسؤول المشتريات', 'program_id' => 1, 'department_id' => 11, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'Accountant', 'title_ar' => 'محاسب', 'program_id' => 1, 'department_id' => 8, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'MEAL Manager', 'title_ar' => 'مسؤول مراقبة وتقييم', 'program_id' => 1, 'department_id' => 9, 'job_level_id' => 3, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'IT Specialist', 'title_ar' => 'متخصص تكنولوجيا المعلومات', 'program_id' => 1, 'department_id' => 5, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'Communication Officer / Photographer', 'title_ar' => 'مسؤول تواصل / مصور', 'program_id' => 1, 'department_id' => 6, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 2,],
        ['title' => 'Public Relations Officer', 'title_ar' => 'مسؤول علاقات عامة', 'program_id' => 1, 'department_id' => 6, 'job_level_id' => 4, 'city_id' => 4,'center_id' => 2,],
    ]


];
