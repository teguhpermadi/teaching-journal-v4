<?php

use App\AcademicStatusCalendarEnum;

test('enum has EFFECTIVE and NOT_EFFECTIVE cases', function () {
    $cases = AcademicStatusCalendarEnum::cases();

    expect($cases)->toHaveCount(2)
        ->and($cases[0])->toBe(AcademicStatusCalendarEnum::EFFECTIVE)
        ->and($cases[1])->toBe(AcademicStatusCalendarEnum::NOT_EFFECTIVE);
});

test('EFFECTIVE has correct value, label, and color', function () {
    $case = AcademicStatusCalendarEnum::EFFECTIVE;

    expect($case->value)->toBe('Efektif')
        ->and($case->getLabel())->toBe('Efektif')
        ->and($case->getColor())->toBe('success');
});

test('NOT_EFFECTIVE has correct value, label, and color', function () {
    $case = AcademicStatusCalendarEnum::NOT_EFFECTIVE;

    expect($case->value)->toBe('Tidak Efektif')
        ->and($case->getLabel())->toBe('Tidak Efektif')
        ->and($case->getColor())->toBe('danger');
});

test('enum is string-backed', function () {
    expect(AcademicStatusCalendarEnum::EFFECTIVE)->toBeInstanceOf(BackedEnum::class)
        ->and(AcademicStatusCalendarEnum::EFFECTIVE->value)->toBeString();
});
