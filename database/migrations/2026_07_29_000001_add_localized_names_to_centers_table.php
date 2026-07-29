<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->renameColumn('name', 'name_en');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name_en');
        });

        $translations = [
            ['name_en' => 'Hope Center/Head Office', 'city_id' => 2, 'name_ar' => 'مركز الرجاء المسيحي المكتب الرئيسي'],
            ['name_en' => 'Hope Center/Micro - Homs', 'city_id' => 3, 'name_ar' => 'مركز الرجاء المسيحي/المشاريع الصغيرة حمص'],
            ['name_en' => 'Hope Center/Micro - Damascus', 'city_id' => 4, 'name_ar' => 'مركز الرجاء المسيحي/المشاريع الصغيرة الشام'],
            ['name_en' => 'Hope Center/Micro - Latakia', 'city_id' => 5, 'name_ar' => 'مركز الرجاء المسيحي/المشاريع الصغيرة اللاذقية'],
            ['name_en' => 'Micro Project', 'city_id' => 16, 'name_ar' => 'المشاريع الصغيرة حلب'],
            ['name_en' => 'Children University', 'city_id' => 2, 'name_ar' => 'اكاديمية الاطفال حلب'],
            ['name_en' => 'Children University', 'city_id' => 3, 'name_ar' => 'اكاديمية الاطفال حمص'],
            ['name_en' => 'ADISC', 'city_id' => 2, 'name_ar' => 'مشروع التأهيل والتكوين حلب'],
            ['name_en' => 'HDISC', 'city_id' => 3, 'name_ar' => 'مشروع التأهيل والتكوين حمص'],
            ['name_en' => 'DDISC', 'city_id' => 4, 'name_ar' => 'مشروع التأهيل والتكوين الشام'],
            ['name_en' => 'LDISC', 'city_id' => 5, 'name_ar' => 'مشروع التأهيل والتكوين اللاذقية'],
            ['name_en' => 'Science Zone', 'city_id' => 3, 'name_ar' => 'ستادي زون ساينس حمص'],
            ['name_en' => 'Science Zone', 'city_id' => 2, 'name_ar' => 'ستادي زون ساينس حلب'],
            ['name_en' => 'Study Zone Al-Nouzha', 'city_id' => 3, 'name_ar' => 'ستادي زون النزهة'],
            ['name_en' => 'Study Zone Al-Mashta', 'city_id' => 16, 'name_ar' => 'ستادي زون المشتى'],
            ['name_en' => 'Study Zone Armenian', 'city_id' => 3, 'name_ar' => 'ستادي زون الأرمن'],
            ['name_en' => 'Study Zone Al-Muzayneh', 'city_id' => 16, 'name_ar' => 'ستادي زون المزينة'],
            ['name_en' => 'Study Zone Bab-Sharqi', 'city_id' => 4, 'name_ar' => 'ستادي زون باب شرقي'],
            ['name_en' => 'Study Zone Bab-Touma', 'city_id' => 4, 'name_ar' => 'ستادي زون باب توما'],
            ['name_en' => 'Study Zone Ziraa 1', 'city_id' => 5, 'name_ar' => 'ستادي زون الزراعة 1'],
            ['name_en' => 'Study Zone Ziraa 2', 'city_id' => 5, 'name_ar' => 'ستادي زون الزراعة 2'],
            ['name_en' => 'Study Zone Al-Haram', 'city_id' => 5, 'name_ar' => 'ستادي زون الهرم'],
            ['name_en' => 'Study Zone Tartous', 'city_id' => 6, 'name_ar' => 'ستادي زون طرطوس'],
            ['name_en' => 'LEAP Aleppo', 'city_id' => 2, 'name_ar' => 'تكوين القادة حلب'],
            ['name_en' => 'LEAP Homs', 'city_id' => 3, 'name_ar' => 'تكوين القادة حمص'],
            ['name_en' => 'Study Zone Al Inshaat', 'city_id' => 3, 'name_ar' => 'ستادي زون الانشائات'],
            ['name_en' => 'Al Wadi Training Center', 'city_id' => 16, 'name_ar' => 'مركز تدريب الوادي'],
        ];

        foreach ($translations as $translation) {
            DB::table('centers')
                ->where('name_en', $translation['name_en'])
                ->where('city_id', $translation['city_id'])
                ->update(['name_ar' => $translation['name_ar']]);
        }

        // Preserve any custom center that is not part of the supplied list.
        DB::table('centers')
            ->whereNull('name_ar')
            ->update(['name_ar' => DB::raw('name_en')]);

        Schema::table('centers', function (Blueprint $table) {
            $table->string('name_ar')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->renameColumn('name_en', 'name');
        });
    }
};
