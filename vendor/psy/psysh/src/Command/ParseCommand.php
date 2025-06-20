<?php

/*
 * This file is part of Psy Shell.
 *
 * (c) 2012-2023 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Psy\Command;

use PhpParser\Node;
use PhpParser\Parser;
use Psy\Context;
use Psy\ContextAware;
use Psy\Input\CodeArgument;
use Psy\ParserFactory;
use Psy\VarDumper\Presenter;
use Psy\VarDumper\PresenterAware;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Caster\Caster;

/**
 * Parse PHP code and show the abstract syntax tree.
 */
class ParseCommand extends Command implements ContextAware, PresenterAware
{
    /**
     * Context instance (for ContextAware interface).
     *
     * @var Context
     */
    protected $context;

    private $presenter;
    private $parserFactory;
    private $parsers;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        $this->parserFactory = new ParserFactory();
        $this->parsers = [];

        parent::__construct($name);
    }

    /**
     * ContextAware interface.
     *
     * @param Context $context
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * PresenterAware interface.
     *
     * @param Presenter $presenter
     */
    public function setPresenter(Presenter $presenter)
    {
        $this->presenter = clone $presenter;
        $this->presenter->addCasters([
            Node::class => function (Node $node, array $a) {
                $a = [
                    Caster::PREFIX_VIRTUAL.'type'       => $node->getType(),
                    Caster::PREFIX_VIRTUAL.'attributes' => $node->getAttributes(),
                ];

                foreach ($node->getSubNodeNames() as $name) {
                    $a[Caster::PREFIX_VIRTUAL.$name] = $node->$name;
                }

                return $a;
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $kindMsg = 'One of PhpParser\\ParserFactory constants: '
            .\implode(', ', ParserFactory::getPossibleKinds())
            ." (default is based on current interpreter's version).";

        $this
            ->setName('parse')
            ->setDefinition([
            new CodeArgument('code', CodeArgument::REQUIRED, 'PHP code to parse.'),
            new InputOption('depth', '', InputOption::VALUE_REQUIRED, 'Depth to parse.', 10),
            new InputOption('kind', '', InputOption::VALUE_REQUIRED, $kindMsg, $this->parserFactory->getDefaultKind()),
        ])
            ->setDescription('Parse PHP code and show the abstract syntax tree.')
            ->setHelp(
                <<<'HELP'
Parse PHP code and show the abstract syntax tree.

This command is used in the development of PsySH. Given a string of PHP code,
it pretty-prints the PHP Parser parse tree.

See https://github.com/nikic/PHP-Parser

It prolly won't be super useful for most of you, but it's here if you want to play.
HELP
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('code');
        $parserKind = $input->getOption('kind');
        $depth = $input->getOption('depth');

        $nodes = $this->getParser($parserKind)->parse($code);
        $output->page($this->presenter->present($nodes, $depth));

        $this->context->setReturnValue($nodes);

        return 0;
    }

    /**
     * Get (or create) the Parser instance.
     *
     * @param string|null $kind One of Psy\ParserFactory constants (only for PHP parser 2.0 and above)
     */
    private function getParser(?string $kind = null): CodeArgumentParser
    {
        if (!\array_key_exists($kind, $this->parsers)) {
            $this->parsers[$kind] = new CodeArgumentParser($this->parserFactory->createParser($kind));
        }

        return $this->parsers[$kind];
    }
}
