<?php

declare(strict_types=1);

namespace App\Me;

use App\Me\Resource\MyProfileLinksResource;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\ConsultPersonOutput;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * Presents what PersonalData shows of my profile: my card, and a link per intent it offers me. One presenter serves one
 * call (ADR 0085).
 */
final class MyProfilePresenter implements ConsultPersonOutput
{
    private const array ROUTES = [
        'Rename' => ['changeMyName', 'api_me_change_name'],
        'ChangeContactDetails' => ['changeMyContactDetails', 'api_me_change_contact_details'],
        'ChangePicture' => ['changeMyPicture', 'api_me_change_picture'],
    ];

    public private(set) PersonCard $me;

    public private(set) MyProfileLinksResource $links;

    public function __construct(private readonly Links $routes)
    {
    }

    public function present(PersonCard $me): void
    {
        $this->me = $me;
        $this->links = new MyProfileLinksResource(...$this->possible($me->possibleIntents));
    }

    /**
     * @param list<string> $intents
     *
     * @return array<string, LinkResource> by link name
     */
    private function possible(array $intents): array
    {
        $possible = [];

        foreach ($intents as $intent) {
            if (isset(self::ROUTES[$intent])) {
                [$name, $route] = self::ROUTES[$intent];
                $possible[$name] = $this->routes->to($route);
            }
        }

        return $possible;
    }
}
