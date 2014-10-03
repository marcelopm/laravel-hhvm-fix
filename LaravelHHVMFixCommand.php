<?php

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class LaravelHHVMFixCommand extends Command
{

    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laravel:hhvm-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Laravel for HHVM';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $folder = $this->input->getOption('folder');

        if (!$folder) {
            throw new \Exception('The "--folder" option is required');
        }

        $confirmMessage = sprintf('Are you sure you want to apply HHVM fix on all files under "%s" folder', $folder);

        // requires confirm in any environment
        if (!$this->confirmToProceed($confirmMessage, function() {
                    return true;
                })) {
            return false;
        }

        $dir = realpath($folder);
        $this->browse($dir);
    }

    public function browse($dir)
    {
        foreach (new DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }

            // if is a directory, go inside
            if ($item->isDir()) {
                $this->browse($item->getPathname());
            } else {
                // if it's a php file
                if ($item->getExtension() === 'php') {
                    $contents = file_get_contents($item->getPathname());

                    // search for compact ( ... )
                    $matches = array();
                    if (preg_match_all('/compact\s?\([^\)]+\)/', $contents, $matches)) {

                        foreach (array_pop($matches) as $compactString) {

                            // get the params
                            $paramsMatches = array();
                            preg_match('/\([^\)]+\)/', $compactString, $paramsMatches);

                            foreach ($paramsMatches as $paramsMatch) {
                                // clean the params
                                $params = explode(',', preg_replace('/\(|\)|\s|\'/', '', $paramsMatch));

                                // build a new param string
                                $newParams = array();
                                foreach ($params as $param) {
                                    /**
                                     * Checking if each variable set is necessary due this statement on PHP's compact function doc page:
                                     * "Any strings that are not set will simply be skipped."
                                     * [http://php.net/manual/en/function.compact.php]
                                     */
                                    $newParams[] = sprintf("'%s' => isset($%s) ? $%s : null", $param, $param, $param);
                                }

                                // build a replacement string: array ('n' => $n[, ...])
                                $arrayString = sprintf('array(%s)', implode(',', $newParams));

                                // replace the compact bit with array bit
                                $contents = str_replace($compactString, $arrayString, $contents);
                            }
                        }

                        // save changes to the file
                        file_put_contents($item->getPathname(), $contents);
                    }
                }
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('folder', null, InputOption::VALUE_REQUIRED, 'The folder containing the files which the fix will be applied to'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run.'),
        );
    }

}
