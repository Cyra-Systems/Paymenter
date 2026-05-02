<?php

namespace App\Admin\Clusters;

use Filament\Clusters\Cluster;

class Domains extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'ri-global-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-global-fill';

    public static string|\UnitEnum|null $navigationGroup = 'Domains';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'domains';
}
