<?php


namespace Cruxinator\Attachments\Models;


use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Venue;
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

    public const DEFAULT_BEER_IMAGE_KEY = 'default_'.Beer::class;
    public const BREWERY_LOGO_IMAGE = 'logo_'.Brewery::class;
    public const BREWERY_MAIN_IMAGE = 'main_'.Brewery::class;
    public const BREWERY_GALLERY_IMAGE = 'gallery_'.Brewery::class;
    public const VENUE_LOGO_IMAGE = 'logo_'.Venue::class;
    public const VENUE_MAIN_IMAGE = 'main_'.Venue::class;
    public const VENUE_GALLERY_IMAGE = 'gallery_'.Venue::class;
    public const VENUE_MENU_HEADER_IMAGE = 'menu_header_'.Venue::class;
    public const BEER_MAIN_IMAGE = 'main_'.Beer::class;
    public const BEER_GALLERY_IMAGE = 'gallery_'.Beer::class;
    public const MAIN_IMAGE = 'main';
    public const ADVERT_IMAGE = 'advert';
    public const BEER_LIST_ADVERT_IMAGE = 'beer_list_advert';
    public const BACKGROUND_IMAGE = 'background';
    public const PRODUCT_IMAGE = 'product';

    public const BEER_LOGOS_DISK = 'beer_logos';

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