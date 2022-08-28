<?php

namespace Cruxinator\Attachments\Models;

use function imagecolorat;
use function imagecolorsforindex;
use function imagesx;
use function imagesy;

/**
 * Class Picture
 * @package App\Models\Attachments
 * @property-read ?int $luminance
 */
class Picture extends Media
{
    public static $singleTableSubclasses = [ResizablePicture::class];

    protected $fillable = ['key'];

    public function getHtml(): string
    {
        return html()
            ->img()
            ->src($this->url)
            ->class("img-responsive")
            ->alt("")
            ->style('min-width: 60px; min-height: 60px;')
            ->toHtml();
    }

    /** @noinspection PhpUnused is laravel attribute*/
    public function getLuminanceAttribute(): ?int
    {
        $self = $this;

        return $this->getMetadata(
            'image.luminance',
            function () use ($self) {
                return $self->getAvgLuminance();
            }
        );
    }

    protected function getAvgLuminance(int $num_samples = 10): ?int
    {
        $contents = $this->getContents();
        $img = @imagecreatefromstring($contents);

        $width = imagesx($img);
        $height = imagesy($img);

        $x_step = intval($width / $num_samples);
        $y_step = intval($height / $num_samples);

        $maxBallast = intval($width / $x_step) * intval($height / $y_step);

        $total_lum = 0;

        $sample_no = 0;

        for ($x = 0; $x < $width; $x += $x_step) {
            for ($y = 0; $y < $height; $y += $y_step) {
                // modify this to handle transparency?
                $rgb = imagecolorat($img, $x, $y);
                $payload = imagecolorsforindex($img, $rgb);
                $r = $payload['red'];
                $g = $payload['green'];
                $b = $payload['blue'];
                $alpha = $payload['alpha'];
                // convert 7-bit alpha to decimal opacity value
                $ballast = 1 - $alpha / 127;

                // choose a simple luminance formula from here
                // http://stackoverflow.com/questions/596216/formula-to-determine-brightness-of-rgb-color
                $lum = ($r + $r + $b + $g + $g + $g) / 6;

                $total_lum += ($lum * $ballast);

                // debugging code
                //           echo "$sample_no - XY: $x,$y = $r, $g, $b = $lum<br />";
                $sample_no += $ballast;
            }
        }

        if (0.01 > $sample_no) {
            return null;
        }

        if (0.01 > abs($sample_no - $maxBallast)) {
            return null;
        }

        // work out the average
        return round($total_lum / $sample_no);
    }
}
