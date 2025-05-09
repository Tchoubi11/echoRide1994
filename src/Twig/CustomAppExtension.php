<?php 

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CustomAppExtension extends AbstractExtension  
{
    public function getFilters()
    {
        return [
            new TwigFilter('star_rating', [$this, 'getStarRating'], ['is_safe' => ['html']]),
        ];
    }

    public function getStarRating($rating): string
    {
        if (!is_numeric($rating) || $rating < 0) return 'Non noté';
        $rounded = min(5, max(0, round($rating)));
        return str_repeat('★', $rounded) . str_repeat('☆', 5 - $rounded);
    }
}
