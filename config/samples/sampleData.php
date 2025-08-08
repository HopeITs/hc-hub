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
        ['name' => 'HeadOffice/ Aleppo', 'description' => null, 'email' => 'headoffice@hcsyria.org'],
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

    'projects' => [
        [
            'name' => [
                'en' => 'Hope Center',
                'ar' => 'مركز الرجاء',
            ],
            'description' => [
                'en' => 'An ecumenical Christian organization established in 2018 under the guidance of the Latin Church in Syria. It empowers Syrian families through diverse initiatives across relief, livelihood, development, and education.',
                'ar' => 'مركز الرجاء المسيحي منظمة مسكونية، تأسست مطلع عام ٢٠١٨، وتعمل بتوجيه من الكنيسة اللاتينية في سورية. رسالتنا هي تمكين الأسر السورية من خلال مبادرات متعددة تستهدف كل مكون من مكونات الأسرة، في مجالات الإغاثة، ودعم سبل العيش، والتنمية، والتعليم.',
            ],
            'slug' => 'HC',
            'code' => '001',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Micro Project',
                'ar' => 'مشاريع صغيرة',
            ],
            'description' => [
                'en' => 'Aimed at empowering Syrian families via livelihood, education, and development programs.',
                'ar' => 'مركز الرجاء المسيحي منظمة مسكونية، تأسست مطلع عام ٢٠١٨، وتعمل بتوجيه من الكنيسة اللاتينية في سورية. رسالتنا هي تمكين الأسر السورية من خلال مبادرات متعددة تستهدف كل مكون من مكونات الأسرة، في مجالات الإغاثة، ودعم سبل العيش، والتنمية، والتعليم.',
            ],
            'slug' => 'MP',
            'code' => '002',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Relief Programs',
                'ar' => 'برامج الإغاثة',
            ],
            'description' => [
                'en' => 'Aims to reduce suffering caused by war and economic crises through essential humanitarian support.',
                'ar' => 'الهدف من البرامج الإغاثية هو تخفيف المعاناة الناجمة عن الحرب السورية وتداعياتها الاقتصادية المعقدة، والتي تُثقل كاهل غالبية الأسر التي تعيش تحت خط الفقر. هدفنا هو مساعدة هذه الأسرعلى تلبية احتياجاتها الأساسية والحفاظ على كرامتها.',
            ],
            'slug' => 'RLF',
            'code' => '003',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Family to Family )Rodzina Rodzinie(',
                'ar' => 'من عائلة إلى عائلة',
            ],
            'description' => [
                'en' => 'Aims to reduce suffering caused by war and economic crises through essential humanitarian support.',
                'ar' => 'الهدف من البرامج الإغاثية هو تخفيف المعاناة الناجمة عن الحرب السورية وتداعياتها الاقتصادية المعقدة، والتي تُثقل كاهل غالبية الأسر التي تعيش تحت خط الفقر. هدفنا هو مساعدة هذه الأسرعلى تلبية احتياجاتها الأساسية والحفاظ على كرامتها.',
            ],
            'slug' => 'F2F',
            'code' => '003',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Assistance for Earthquake',
                'ar' => 'الإستجابة للزلزال',
            ],
            'description' => [
                'en' => 'Supports post-war recovery through rebuilding infrastructure, schools, homes, and implementing sustainable energy solutions.',
                'ar' => 'يُعدّ عمل إعادة التأهيل في فترة ما بعد الحرب أمرًا بالغ الأهمية للتعافي المبكر للمجتمعات. كما أنه ضروري لاستعادة البنى التحتية المتضررة وخدمات الدعم التي تُمكّن الناس من استئناف حياتهم. وقد ساهم مركز الأمل المسيحي في هذا المجال من خلال إعادة تأهيل المرافق التعليمية والمجتمعية المدمرة، مثل المدارس والملاعب، وترميم المنازل وأماكن العمل. كما امتدت جهودنا إلى دعم المجتمعات الريفية من خلال توفير البنية التحتية الكهروضوئية لإنتاج الكهرباء واستخراج المياه في عدة قرى.',
            ],
            'slug' => 'MCC',
            'code' => '004',
            'is_active' => false,
        ],
        [
            'name' => [
                'en' => 'School to School )Szkoła szkole(',
                'ar' => 'من مدرسة إلى مدرسة',
            ],
            'description' => [
                'en' => 'Supports post-war recovery through rebuilding infrastructure, schools, homes, and implementing sustainable energy solutions.',
                'ar' => 'يُعدّ عمل إعادة التأهيل في فترة ما بعد الحرب أمرًا بالغ الأهمية للتعافي المبكر للمجتمعات. كما أنه ضروري لاستعادة البنى التحتية المتضررة وخدمات الدعم التي تُمكّن الناس من استئناف حياتهم. وقد ساهم مركز الأمل المسيحي في هذا المجال من خلال إعادة تأهيل المرافق التعليمية والمجتمعية المدمرة، مثل المدارس والملاعب، وترميم المنازل وأماكن العمل. كما امتدت جهودنا إلى دعم المجتمعات الريفية من خلال توفير البنية التحتية الكهروضوئية لإنتاج الكهرباء واستخراج المياه في عدة قرى.',
            ],
            'slug' => 'S2S',
            'code' => '004',
            'is_active' => false,
        ],
        [
            'name' => [
                'ar' => 'مركز التدريب والتكوين',
                'en' => 'Development Impact and Support Center',
            ],
            'description' => [
                'en' => 'Supports fresh graduates in developing foundational, employability, and peer mentorship skills to enter the job market.',
                'ar' => 'الهدف من برنامج DISC في مساعدة الخريجين الجدد على توجيه أنفسهم نحو العمل وتزويدهم بالمهارات اللازمة لضمان استعدادهم لدخول سوق العمل. يعمل المشروع على ثلاثة جوانب أساسية في آنٍ واحد: المهارات الأساسية، ومهارات التوظيف، والرفقة لتبادل الخبرات والدعم.',
            ],
            'slug' => 'DISC',
            'code' => '005',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Study Zone',
                'ar' => 'ستادي زون',
            ],
            'description' => [
                'en' => null,
                'ar' => null,
            ],
            'slug' => 'SZ',
            'code' => '006',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Homework Room',
                'ar' => 'هوم وورك روم',
            ],
            'description' => [
                'en' => null,
                'ar' => null,
            ],
            'slug' => 'HWR',
            'code' => '007',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Children University',
                'ar' => 'أكاديمية الأطفال',
            ],
            'description' => [
                'en' => 'An interactive educational program for children aged 6 to 16, designed to simulate university-style learning through experience.',
                'ar' => 'جامعة الأطفال برنامج تعليمي مصمم خصيصًا للأطفال من سن 6 إلى 16 عامًا، يشبه المقررات الجامعية، بأسلوب تفاعلي متقدم ومبتكر، يركز على التجربة كأداة تعليمية، بالإضافة إلى أساليب متنوعة أخرى. يتكون البرنامج من مرحلتين: مستوى أساسي ومستوى متقدم، مدة كل مرحلة 4 أشهر، ويتكون كل مستوى من يومين دراسيين ويوم واحد للأنشطة أسبوعيًا.',
            ],
            'slug' => 'CU',
            'code' => '008',
            'is_active' => true,
        ],
        [
            'name' => [
                'en' => 'Sha8er',
                'ar' => 'منصة شاغر',
            ],
            'description' => [
                'en' => null,
                'ar' => null,
            ],
            'slug' => 'Sha8er',
            'code' => '009',
            'is_active' => true,
        ],
        [
            'name' => [
                'ar' => 'مشروع ريادة الأعمال',
                'en' => 'Entrepreneurship project',
            ],
            'description' => [
                'en' => 'Leadership program to equip community leaders with technical and leadership skills for effective crisis response.',
                'ar' => 'نظرًا لعدم وجود قادة ناجحين يمكنهم توجيه المجتمعات المحلية خلال أوقات الأزمات، تم إنشاء برنامج القيادة لاستهداف الأعضاء النشطين في المجتمع وتزويدهم بالمهارات الفنية والقدرات القيادية، والتي ستدعمهم في التقييم الدقيق لاحتياجات مجتمعاتهم الحالية والمستقبلية واقتراح حلول فعالة، وبالتالي المساهمة الفعلية على تشجيع تنمية المجتمع.',
            ],
            'slug' => 'ESP',
            'code' => '009',
            'is_active' => true,
        ],
        [
            'name' => [
                'ar' => 'برنامج تطوير وتمكين القادة',
                'en' => 'Leadership Empowerment & Achievement Program',
            ],
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
        ['name' => ['en' => 'Hope Center', 'ar' => 'مركز الرجاء'], 'city_id' => 2, 'project_id' => 1, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => ['en' => 'Hope Center', 'ar' => 'مركز الرجاء'], 'city_id' => 3, 'project_id' => 1, 'address' => 'Damascus', 'is_active' => true],
        ['name' => ['en' => 'Hope Center', 'ar' => 'مركز الرجاء'], 'city_id' => 4, 'project_id' => 1, 'address' => 'Latakia', 'is_active' => true],
        ['name' => ['en' => 'Hope Center', 'ar' => 'مركز الرجاء'], 'city_id' => 5, 'project_id' => 1, 'address' => 'Homs', 'is_active' => true],
        ['name' => ['en' => 'Micro Project', 'ar' => 'المشاريع الصغيرة'], 'city_id' => 2, 'project_id' => 2, 'address' => 'Homs', 'is_active' => true],
        ['name' => ['en' => 'Micro Project', 'ar' => 'المشاريع الصغيرة'], 'city_id' => 3, 'project_id' => 2, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => ['en' => 'Micro Project', 'ar' => 'المشاريع الصغيرة'], 'city_id' => 4, 'project_id' => 2, 'address' => 'Damascus', 'is_active' => true],
        ['name' => ['en' => 'Micro Project', 'ar' => 'المشاريع الصغيرة'], 'city_id' => 5, 'project_id' => 2, 'address' => 'Latakia', 'is_active' => true],
        ['name' => ['en' => 'Relief programs', 'ar' => 'برامج الاغاثة'], 'city_id' => 2, 'project_id' => 3, 'address' => 'Damascus', 'is_active' => true],
        ['name' => ['en' => 'Sha8er', 'ar' => 'شاغر'], 'city_id' => 1, 'project_id' => 7, 'address' => null, 'is_active' => true],
        ['name' => ['en' => 'Children University', 'ar' => 'أكاديمية الأطفال'], 'city_id' => 2, 'project_id' => 4, 'address' => 'Homs', 'is_active' => true],
        ['name' => ['en' => 'Children University', 'ar' => 'أكاديمية الأطفال'], 'city_id' => 3, 'project_id' => 4, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => ['en' => 'ADISC', 'ar' => 'التأهيل والتمكين'], 'city_id' => 2, 'project_id' => 5, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => ['en' => 'HDISC', 'ar' => 'التأهيل والتمكين'], 'city_id' => 3, 'project_id' => 5, 'address' => 'Homs', 'is_active' => true],
        ['name' => ['en' => 'DDISC', 'ar' => 'التأهيل والتمكين'], 'city_id' => 4, 'project_id' => 5, 'address' => 'Damascus', 'is_active' => true],
        ['name' => ['en' => 'LDISC', 'ar' => 'التأهيل والتمكين'], 'city_id' => 5, 'project_id' => 5, 'address' => 'Latakia', 'is_active' => false],
        ['name' => ['en' => 'Science Zone', 'ar' => 'ساينس زون'], 'city_id' => 2, 'project_id' => 8, 'address' => 'Homs', 'is_active' => true],
        ['name' => ['en' => 'Science Zone', 'ar' => 'ساينس زون'], 'city_id' => 3, 'project_id' => 8, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون النزهة'], 'city_id' => 3, 'project_id' => 8, 'address' => 'Al-Nouzha', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون المحطة'], 'city_id' => 3, 'project_id' => 8, 'address' => 'Al-Mashta', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون الارمن'], 'city_id' => 3, 'project_id' => 8, 'address' => 'Armenian', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون المزينة'], 'city_id' => 3, 'project_id' => 8, 'address' => 'Al-Muzayneh', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون باب شرقي'], 'city_id' => 4, 'project_id' => 8, 'address' => 'Bab-Sharqi', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون باب توما'], 'city_id' => 4, 'project_id' => 8, 'address' => 'Bab-Touma', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون زراعة 1'], 'city_id' => 5, 'project_id' => 8, 'address' => 'Ziraa 1', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون زراعة 2'], 'city_id' => 5, 'project_id' => 8, 'address' => 'Ziraa 2', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون 8 اذار'], 'city_id' => 5, 'project_id' => 8, 'address' => 'Al-Haram', 'is_active' => true],
        ['name' => ['en' => 'Study Zone', 'ar' => 'ستادي زون طرطوس'], 'city_id' => 6, 'project_id' => 8, 'address' => 'Tartous', 'is_active' => true],
        ['name' => ['en' => 'LEAP', 'ar' => 'إعداد القادة'], 'city_id' => 2, 'project_id' => 6, 'address' => 'Aleppo', 'is_active' => true],
        ['name' => ['en' => 'LEAP', 'ar' => 'إعداد القادة'], 'city_id' => 3, 'project_id' => 6, 'address' => 'Homs', 'is_active' => true],
        ['name' => ['en' => 'Rehabiliation', 'ar' => 'إعادة التأهيل'], 'city_id' => 2, 'project_id' => 9, 'address' => 'Aleppo', 'is_active' => true],
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
        ['name' => ['en' => 'Board', 'ar' => 'مجلس الإدارة'], 'description' => null, 'grade' => 1, 'email' => 'board@hcsyria.org'],
        ['name' => ['en' => 'Program M', 'ar' => 'مدير البرنامج'], 'description' => null, 'grade' => 2, 'email' => 'managers@hcsyria.org'],
        ['name' => ['en' => 'Project M', 'ar' => 'مدير المشروع'], 'description' => null, 'grade' => 3, 'email' => 'managers@hcsyria.org'],
        ['name' => ['en' => 'Department M', 'ar' => 'مدير القسم'], 'description' => null, 'grade' => 4, 'email' => 'managers@hcsyria.org'],
        ['name' => ['en' => 'Team', 'ar' => 'الفريق'], 'description' => null, 'grade' => 5, 'email' => 'team@hcsyria.org'],
        ['name' => ['en' => 'Service Agreement', 'ar' => 'اتفاقية الخدمة'], 'description' => null, 'grade' => 6, 'email' => null],
        ['name' => ['en' => 'Keepers', 'ar' => 'المستخدمون'], 'description' => null, 'grade' => 7, 'email' => null],
    ],

    'requiredPositions' => [
        [
            'project_id' => 1,
            'title' => 'Software Developer',
            'qualifications' => 'Bachelor in Computer Science or related field.',
            'is_active' => true,
        ],
        [
            'project_id' => 2,
            'title' => 'HR Manager',
            'qualifications' => 'Degree in HR Management with 3+ years experience.',
            'is_active' => true,
        ],
    ],
];
