<?php

namespace App\Enums;

enum FeedStage: string
{
    case Piglet = 'piglet';

    case Grower = 'grower';

    case Finisher = 'finisher';

    case Sow = 'sow';
}
