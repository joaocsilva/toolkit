<?php

declare(strict_types=1);

namespace EcEuropa\Toolkit\TaskRunner\Commands;

use EcEuropa\Toolkit\TaskRunner\AbstractCommands;
use EcEuropa\Toolkit\Toolkit;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\ResultData;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputOption;

class DocumentationCommands extends AbstractCommands
{

    /**
     * The GitHub token.
     *
     * @var string
     */
    private string $token;

    /**
     * The documentation directory.
     *
     * @var string
     */
    private string $docsDir;

    /**
     * A temporary directory.
     *
     * @var string
     */
    private string $tmpDir;

    /**
     * The repository where the documentation is.
     *
     * @var string
     */
    private string $repo;

    /**
     * The documentation branch.
     *
     * @var string
     */
    private string $branch;

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFile()
    {
        return Toolkit::getToolkitRoot() . '/config/commands/documentation.yml';
    }

    /**
     * Generate the documentation
     *
     * @command toolkit:generate-documentation
     *
     * @option token    The GitHub token to use.
     * @option repo     The repository.
     * @option docs-dir The documentation directory.
     * @option tmp-dir  The temporary directory.
     * @option branch   The documentation branch.
     *
     * @hidden
     *
     * @aliases tk-docs
     */
    public function toolkitGenerateDocumentation(ConsoleIO $io, array $options = [
        'token' => InputOption::VALUE_REQUIRED,
        'repo' => InputOption::VALUE_REQUIRED,
        'docs-dir' => InputOption::VALUE_REQUIRED,
        'tmp-dir' => InputOption::VALUE_REQUIRED,
        'branch' => InputOption::VALUE_REQUIRED,
    ])
    {
        if (empty($options['token']) || $options['token'] === '${env.GITHUB_API_TOKEN}') {
            $io->error('The env var GITHUB_API_TOKEN is required.');
            return ResultData::EXITCODE_ERROR;
        }

        if (!$this->downloadPhpDocPhar()) {
            $io->error('Fail to download the phpDocumentor.phar file.');
            return ResultData::EXITCODE_ERROR;
        }

        $this->token = $options['token'];
        $this->repo = $options['repo'];
        $this->docsDir = $options['docs-dir'];
        $this->tmpDir = $options['tmp-dir'];
        $this->branch = $options['branch'];
        $builder = $this->collectionBuilder();

        if (file_exists($this->tmpDir)) {
            $builder->addTask($this->taskDeleteDir($this->tmpDir));
        }

        return $builder
            // Backup relevant files.
            ->addTaskList($this->backupRelevantFiles())
            // Clean up documentation folder.
            ->addTask($this->cleanDir($this->docsDir))
            // Restore stored files.
            ->addTask($this->taskCopyDir([$options['tmp-dir'] => $this->docsDir]))
            // Generate documentation.
            ->addTask($this->taskExec($this->getBin('phpDoc')))
            // Clean up temporary folder.
            ->addTask($this->cleanDir($this->tmpDir))
            // Clone documentation.
            ->addTaskList($this->gitClone())
            // Clean up before copy new content.
            ->addTask($this->cleanDir($this->tmpDir, false))
            // Copy generated docs.
            ->addTask($this->taskCopyDir([$this->docsDir => $this->tmpDir]))
            // Commit and push.
            ->addTask($this->gitAddCommitPush())
            // Delete temporary folder.
            ->addTask($this->taskDeleteDir($this->tmpDir));
    }

    /**
     * Save the relevant files: index.rst and guide/!*.html.
     */
    private function backupRelevantFiles(): array
    {
        $tasks = [];
        $tasks[] = $this->taskCopyDir([$this->docsDir . '/guide' => $this->tmpDir . '/guide'])
            ->exclude(glob($this->docsDir . '/guide/*.html'));
        $tasks[] = $this->taskFilesystemStack()
            ->copy($this->docsDir . '/index.rst', $this->tmpDir . '/index.rst');
        return $tasks;
    }

    /**
     * Clone the documentation branch (hide output to avoid expose token).
     */
    private function gitClone(): array
    {
        $tasks = [];
        $repo = sprintf($this->repo, $this->token);
        $tasks[] = $this->collectionBuilder()->addCode(function () use ($repo) {
            // Remove the token from the url for output.
            if (str_contains($repo, '@')) {
                $protocol = strstr($repo, '://', true);
                $repo = $protocol . '://' . substr(strstr($repo, '@'), 1);
            }
            $msg = sprintf(
                " <fg=white;bg=cyan;options=bold>[Vcs\GitStack]</> Running <info>git clone --depth 1 %s %s --branch %s</>",
                $repo,
                $this->tmpDir,
                $this->branch
            );
            $this->output()->writeln(['', $msg]);
        });
        $tasks[] = $this->taskGitStack()
            ->cloneShallow($repo, $this->tmpDir, $this->branch)
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG);
        return $tasks;
    }

    /**
     * Git add commit and push to the documentation branch.
     */
    private function gitAddCommitPush()
    {
        return $this->taskExecStack()
            ->stopOnFail()
            ->exec('git -C ' . $this->tmpDir . ' config user.name "Toolkit"')
            ->exec('git -C ' . $this->tmpDir . ' config user.email "DIGIT-NEXTEUROPA-QA@ec.europa.eu"')
            ->exec('git -C ' . $this->tmpDir . ' add .')
            ->exec('git -C ' . $this->tmpDir . ' commit -m "Generate documentation."')
            ->exec('git -C ' . $this->tmpDir . ' push');
    }

    /**
     * Clean up given directory.
     *
     * @param string $directory
     *   The directory to clean
     * @param bool $includeHidden
     *   If true, all hidden files will be removed.
     */
    private function cleanDir(string $directory, bool $includeHidden = true)
    {
        if ($includeHidden) {
            // The task taskCleanDir() removes the .git/ folder and hidden files.
            return $this->taskCleanDir($directory);
        }
        // This glob do not include hidden files or directories.
        return $this->taskFilesystemStack()->remove(glob($directory . '/*'));
    }

    /**
     * Ensure that the phpDoc phar file exists.
     */
    private function downloadPhpDocPhar(): bool
    {
        $phpDoc = $this->getBinPath('phpDoc');
        if (!file_exists($phpDoc)) {
            try {
                file_put_contents($phpDoc, file_get_contents('https://phpdoc.org/phpDocumentor.phar'));
            } catch (\Exception $e) {
                return false;
            }
            if (filesize($phpDoc) <= 0) {
                return false;
            }
            $this->_chmod($phpDoc, 0755);
        }
        return true;
    }

}
