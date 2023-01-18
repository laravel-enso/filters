<?php

namespace LaravelEnso\Filters\Enums;

enum SearchMode: string
{
    case ExactMatch = 'exactMatch';
    case Full = 'full';
    case StartsWith = 'startsWith';
    case EndsWith = 'endsWith';
    case DoesntContain = 'doesntContain';
    case Algolia = 'searchProvider';
}
