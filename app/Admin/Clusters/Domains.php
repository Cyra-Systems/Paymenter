<?php

namespace App\Admin\Clusters;

use Filament\Clusters\Cluster;

/**
 * Sits ABOVE Services in the sidebar by setting navigationSort to a
 * lower value. Services has sort = 0, so we use -1 here.
 */
class Domains extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'ri-global-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-global-fill';

    public static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'domains';

    protected static ?string $title = 'Domains';
}
