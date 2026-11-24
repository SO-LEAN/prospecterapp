<?php

declare(strict_types=1);

namespace App\Shop;

use App\Shop\Resource\CartLinksResource;
use Prospecter\Core\Shop\Application\Cart\ConsultCart\CartView;
use Prospecter\Core\Shop\Application\Cart\ConsultCart\ConsultCartOutput;

final class CartResourcePresenter implements ConsultCartOutput
{
    public ?CartLinksResource $resource = null;

    public function present(CartView $cart): void
    {
        $this->resource = new CartLinksResource(self::linkTo('api_cart_add_item'), checkOut: self::offered(false, 'api_cart_check_out'));
    }

    private static function linkTo(string $route): string
    {
        return $route;
    }

    private static function offered(bool $possible, string $route): ?string
    {
        return $possible ? $route : null;
    }
}
