<?php

declare(strict_types=1);

namespace App\Me\Mcp;

use App\Me\Input\RemindersInput;
use App\Me\MyRemindersReader;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Accounts\Application\ChangeMyReminders\ChangeMyReminders;
use Symfony\Component\Clock\ClockInterface;

/**
 * `PATCH /api/me/reminders`, for an AI (ADR 0163).
 */
#[Adapts('api_me_change_reminders')]
final readonly class ChangeMyRemindersTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyRemindersReader $myRemindersReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param bool $enabled       whether the member is reminded of their exchanges and to-dos
     * @param int  $minutesBefore how long before, in minutes
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-my-reminders', description: 'Turn the reminders of the member on or off, and say how long before their exchanges and to-dos they are reminded. Answers their reminders.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(bool $enabled, int $minutesBefore): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $input = $this->inputs->valid(new RemindersInput($enabled, $minutesBefore));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangeMyReminders(
            memberId: $memberId,
            enabled: $input->enabled ?? throw new \LogicException('Reminders validated are on or off'),
            minutesBefore: $input->minutesBefore ?? throw new \LogicException('Reminders validated have their delay'),
            executedAt: $this->clock->now(),
        ));

        return $this->results->of($this->myRemindersReader->read($memberId));
    }
}
