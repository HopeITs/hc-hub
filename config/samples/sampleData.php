<?php
use App\Enums\ContractPlaceEnum;


return [
    'cities' => [
        ['code' => 'ALE', 'name' => ['en' => 'Aleppo', 'ar' => 'حلب'],        'is_active' => true],
            ['code' => 'DAM', 'name' => ['en' => 'Damascus', 'ar' => 'دمشق'],     'is_active' => true],
            ['code' => 'HOM', 'name' => ['en' => 'Homs', 'ar' => 'حمص'],          'is_active' => true],
            ['code' => 'LAT', 'name' => ['en' => 'Latakia', 'ar' => 'اللاذقية'], 'is_active' => true],
            ['code' => 'TAR', 'name' => ['en' => 'Tartus', 'ar' => 'طرطوس'],      'is_active' => true],

            //Non active cities:
            ['code' => 'HSA', 'name' => ['en' => 'Hasakah', 'ar' => 'الحسكة'],       'is_active' => false],
            ['code' => 'DER', 'name' => ['en' => 'Deir el-zor', 'ar' => 'دير الزور'],'is_active' => false],
            ['code' => 'RAQ', 'name' => ['en' => 'Raqqa', 'ar' => 'الرقة'],          'is_active' => false],
            ['code' => 'IDB', 'name' => ['en' => 'Edleb', 'ar' => 'إدلب'],           'is_active' => false],
            ['code' => 'SUW', 'name' => ['en' => 'Suwaida', 'ar' => 'السويداء'],  'is_active' => false],
            ['code' => 'DAR', 'name' => ['en' => 'Daraa', 'ar' => 'درعا'],           'is_active' => false],
            ['code' => 'QUN', 'name' => ['en' => 'Quneitra', 'ar' => 'القنيطرة'],    'is_active' => false],
            ['code' => 'RIF', 'name' => ['en' => 'Rif Dimashq', 'ar' => 'ريف دمشق'], 'is_active' => false],
            ['code' => 'HAM', 'name' => ['en' => 'Hama', 'ar' => 'حماة'],            'is_active' => false],
    ],

    'ContractType' => [
        [
                'type' => ['en' => 'Full Annual', 'ar' => 'سنوي كامل'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 40,
            ],
            [
                'type' => ['en' => 'Partial Annual', 'ar' => 'سنوي جزئي'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 20,
            ],
            [
                'type' => ['en' => 'Trial Full', 'ar' => 'تجريبي كامل'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 40,
            ],
            [
                'type' => ['en' => 'Trial Partial', 'ar' => 'تجريبي جزئي'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 20,
            ],
            [
                'type' => ['en' => 'Agreement Contract', 'ar' => 'عقد اتفاق'],
                'place' => ContractPlaceEnum::Online,
                'weekly_hours' => 0,
            ],
            [
                'type' => ['en' => 'Agreement Contract', 'ar' => 'عقد اتفاق'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 0,
            ],
        ],

    'departments' => [
        ['name' => 'HeadOffice/ Aleppo',   'description' => null, 'email' => 'headoffice@hcsyria.org'],
            ['name' => 'Front Desk',           'description' => null, 'email' => null],
            ['name' => 'Teachers',             'description' => null, 'email' => null],
            ['name' => 'Trainers/ Coach',      'description' => null, 'email' => null],
            ['name' => 'IT',                   'description' => null, 'email' => 'it@hcsyria.org'],
            ['name' => 'Communication',        'description' => null, 'email' => 'ct@hcsyria.org'],
            ['name' => 'HR',                   'description' => null, 'email' => null],
            ['name' => 'Finance',              'description' => null, 'email' => 'finance@hcsyria.org'],
            ['name' => 'Meal',                 'description' => null, 'email' => null],
            ['name' => 'Fundraising',          'description' => null, 'email' => null],
            ['name' => 'Logistic',             'description' => null, 'email' => null],
            ['name' => 'Legal Affairs',        'description' => null, 'email' => null],
            ['name' => 'Services/ Keepers',    'description' => null, 'email' => null],
    ],

    'projects' => [
        [
                'name' => [
                    'en' => 'Hope Center',
                    'ar' => 'مركز الرجاء'
                ],
                'description' => [
                    'en' => 'An ecumenical Christian organization established in 2018 under the guidance of the Latin Church in Syria. It empowers Syrian families through diverse initiatives across relief, livelihood, development, and education.',
                    'ar' => 'مركز الرجاء المسيحي منظمة مسكونية، تأسست مطلع عام ٢٠١٨، وتعمل بتوجيه من الكنيسة اللاتينية في سورية. رسالتنا هي تمكين الأسر السورية من خلال مبادرات متعددة تستهدف كل مكون من مكونات الأسرة، في مجالات الإغاثة، ودعم سبل العيش، والتنمية، والتعليم.'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Micro Project',
                    'ar' => 'مشاريع صغيرة'
                ],
                'description' => [
                    'en' => 'Aimed at empowering Syrian families via livelihood, education, and development programs.',
                    'ar' => 'مركز الرجاء المسيحي منظمة مسكونية، تأسست مطلع عام ٢٠١٨، وتعمل بتوجيه من الكنيسة اللاتينية في سورية. رسالتنا هي تمكين الأسر السورية من خلال مبادرات متعددة تستهدف كل مكون من مكونات الأسرة، في مجالات الإغاثة، ودعم سبل العيش، والتنمية، والتعليم.'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Relief Programs',
                    'ar' => 'برامج الإغاثة'
                ],
                'description' => [
                    'en' => 'Aims to reduce suffering caused by war and economic crises through essential humanitarian support.',
                    'ar' => 'الهدف من البرامج الإغاثية هو تخفيف المعاناة الناجمة عن الحرب السورية وتداعياتها الاقتصادية المعقدة، والتي تُثقل كاهل غالبية الأسر التي تعيش تحت خط الفقر. هدفنا هو مساعدة هذه الأسرعلى تلبية احتياجاتها الأساسية والحفاظ على كرامتها.'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Children University',
                    'ar' => 'جامعة الأطفال'
                ],
                'description' => [
                    'en' => 'An interactive educational program for children aged 6 to 16, designed to simulate university-style learning through experience.',
                    'ar' => 'جامعة الأطفال برنامج تعليمي مصمم خصيصًا للأطفال من سن 6 إلى 16 عامًا، يشبه المقررات الجامعية، بأسلوب تفاعلي متقدم ومبتكر، يركز على التجربة كأداة تعليمية، بالإضافة إلى أساليب متنوعة أخرى. يتكون البرنامج من مرحلتين: مستوى أساسي ومستوى متقدم، مدة كل مرحلة 4 أشهر، ويتكون كل مستوى من يومين دراسيين ويوم واحد للأنشطة أسبوعيًا.'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'DISC',
                ],
                'description' => [
                    'en' => 'Supports fresh graduates in developing foundational, employability, and peer mentorship skills to enter the job market.',
                    'ar' => 'الهدف من برنامج DISC في مساعدة الخريجين الجدد على توجيه أنفسهم نحو العمل وتزويدهم بالمهارات اللازمة لضمان استعدادهم لدخول سوق العمل. يعمل المشروع على ثلاثة جوانب أساسية في آنٍ واحد: المهارات الأساسية، ومهارات التوظيف، والرفقة لتبادل الخبرات والدعم.'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Leap',
                ],
                'description' => [
                    'en' => 'Leadership program to equip community leaders with technical and leadership skills for effective crisis response.',
                    'ar' => 'نظرًا لعدم وجود قادة ناجحين يمكنهم توجيه المجتمعات المحلية خلال أوقات الأزمات، تم إنشاء برنامج القيادة لاستهداف الأعضاء النشطين في المجتمع وتزويدهم بالمهارات الفنية والقدرات القيادية، والتي ستدعمهم في التقييم الدقيق لاحتياجات مجتمعاتهم الحالية والمستقبلية واقتراح حلول فعالة، وبالتالي المساهمة الفعلية على تشجيع تنمية المجتمع.'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Sha8er',
                    'ar' => 'شاغر'
                ],
                'description' => [
                    'en' => null,
                    'ar' => null
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Rehabilitation',
                    'ar' => 'إعادة التأهيل'
                ],
                'description' => [
                    'en' => 'Supports post-war recovery through rebuilding infrastructure, schools, homes, and implementing sustainable energy solutions.',
                    'ar' => 'يُعدّ عمل إعادة التأهيل في فترة ما بعد الحرب أمرًا بالغ الأهمية للتعافي المبكر للمجتمعات. كما أنه ضروري لاستعادة البنى التحتية المتضررة وخدمات الدعم التي تُمكّن الناس من استئناف حياتهم. وقد ساهم مركز الأمل المسيحي في هذا المجال من خلال إعادة تأهيل المرافق التعليمية والمجتمعية المدمرة، مثل المدارس والملاعب، وترميم المنازل وأماكن العمل. كما امتدت جهودنا إلى دعم المجتمعات الريفية من خلال توفير البنية التحتية الكهروضوئية لإنتاج الكهرباء واستخراج المياه في عدة قرى.'
                ],
                'is_active' => false,
            ],
        ],

];
