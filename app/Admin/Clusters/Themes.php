<?php

namespace App\Admin\Clusters;

use Filament\Clusters\Cluster;

class Themes extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'ri-palette-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-palette-fill';

    public static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'themes';
}
