namespace {namespace};

use Console\BaseCommand;
use Console\CLI;
<?php if ($type === 'generator'): ?>
use Console\GeneratorTrait;
<?php endif ?>

class {class} extends BaseCommand
{
<?php if ($type === 'generator'): ?>
    use GeneratorTrait;

<?php endif ?>
    /**
     * The Command's Group
     */
    protected string $group = '{group}';

    /**
     * The Command's Name
     */
    protected string $name = '{command}';

    /**
     * The Command's Description
     */
    protected string $description = '';

    /**
     * The Command's Usage
     */
    protected string $usage = '{command} [arguments] [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [];

    /**
     * The Command's Options
     */
    protected array $options = [];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
<?php if ($type === 'generator'): ?>
        $this->component = 'Command';
        $this->directory = 'Commands';
        $this->template  = 'command.tpl.php';

        $this->execute($params);
<?php else: ?>
        //
<?php endif ?>
    }
}
