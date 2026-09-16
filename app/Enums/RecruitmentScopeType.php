<?php

namespace App\Enums;

enum RecruitmentScopeType: string
{
    case Organization = 'organization';
    case City = 'city';
    case Governorate = 'governorate';
    case Program = 'program';
    case Project = 'project';
    case Department = 'department';
    case Center = 'center';
    case Position = 'position';
    case User = 'user';

    public static function normalize(?string $value): ?self
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'organization', 'organization-wide', 'organisation', 'all' => self::Organization,
            'city', 'area', 'region' => self::City,
            'governorate' => self::Governorate,
            'program' => self::Program,
            'project' => self::Project,
            'department' => self::Department,
            'center', 'centre' => self::Center,
            'position' => self::Position,
            'user' => self::User,
            default => null,
        };
    }
}
