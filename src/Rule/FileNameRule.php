<?php

namespace DTL\GherkinLint\Rule;

use Cucumber\Messages\GherkinDocument;
use DTL\GherkinLint\Model\FeatureDiagnostic;
use DTL\GherkinLint\Model\FeatureDiagnosticSeverity;
use DTL\GherkinLint\Model\Range;
use DTL\GherkinLint\Model\Rule;
use DTL\GherkinLint\Model\RuleConfig;
use DTL\GherkinLint\Model\RuleDescription;
use DTL\GherkinLint\Model\RuleExample;
use Generator;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\String\UnicodeString;

class FileNameRule implements Rule
{
    public function analyse(GherkinDocument $document, RuleConfig $config): Generator
    {
        assert($config instanceof FileNameConfig);

        $fullPath = $document->uri;
        if (null === $fullPath) {
            return;
        }
        $path = new UnicodeString(Path::getFilenameWithoutExtension($fullPath));

        $converted = match ($config->style) {
            FileNameConfig::PASCAL_CASE => ucfirst($path->camel()->__toString()),
            FileNameConfig::CAMEL_CASE => $path->camel()->__toString(),
            FileNameConfig::SNAKE_CASE => $path->snake()->__toString(),
            FileNameConfig::KEBAB_CASE => $path->snake()->replace('_', '-')->__toString(),
            default => throw new RuntimeException(sprintf(
                'Invalid filename style "%s"',
                $config->style
            )),
        };

        if ($converted === $path->__toString()) {
            return;
        }

        yield new FeatureDiagnostic(
            Range::fromInts(1, 1, 1, 1),
            FeatureDiagnosticSeverity::WARNING,
            sprintf('Filename "%s" should be "%s"', $fullPath, $config->style)
        );
    }

    public function describe(): RuleDescription
    {
        return new RuleDescription(
            'filename',
            'Filenames must conform to the specified stype',
            FileNameConfig::class,
            examples: [
                new RuleExample(
                    title: 'Snake case',
                    valid: true,
                    example: <<<'EOT'
                        Feature: Some feature
                        EOT
                    ,
                    config: new FileNameConfig(
                        style: 'snake_case'
                    ),
                    filename: 'this_is_fine.feature',
                ),
                new RuleExample(
                    title: 'Pascal case',
                    valid: true,
                    example: <<<'EOT'
                        Feature: Some feature
                        EOT
                    ,
                    config: new FileNameConfig(
                        style: 'PascalCase'
                    ),
                    filename: 'ThisIsFine.feature',
                ),
                new RuleExample(
                    title: 'Kebab Case',
                    valid: true,
                    example: <<<'EOT'
                        Feature: Some feature
                        EOT
                    ,
                    config: new FileNameConfig(
                        style: 'kebab-case'
                    ),
                    filename: 'this-is-fine.feature',
                ),
                new RuleExample(
                    title: 'Camel case',
                    valid: true,
                    example: <<<'EOT'
                        Feature: Some feature
                        EOT
                    ,
                    config: new FileNameConfig(
                        style: 'camelCase'
                    ),
                    filename: 'thisIsFine.feature',
                )
            ]
        );
    }

    private function match(string $pattern, string $filename): bool
    {
        return (bool)preg_match($pattern, $filename);
    }
}
