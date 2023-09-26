<?php

namespace Coolhax\CoolhaxCrudGenerator\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
/**
 * Class CoolhaxCrudGenerator.
 *
 * @author  Awais <asargodha@gmail.com>
 */
class CoolhaxCrudGenerator extends CoolhaxGeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud
                            {name : Table name}
                            {--route= : Custom route name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create bootstrap CRUD operations';

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    public function handle()
    {
        $this->info('Running Crud Generator ...');

        $this->table = $this->getNameInput();

        // If table not exist in DB return
        if (!$this->tableExists()) {
            $this->error("`{$this->table}` table not exist");

            return false;
        }

        // Build the class name from table name
        $this->name = $this->_buildClassName();

        // Generate the crud
        $this->buildOptions()
            ->buildController()
            ->buildModel()
            ->buildViews();

        $this->info('Created Successfully.');
        $this->generateRoutes();
        return true;
    }

    /**
     * Build the Controller Class and save in app/Http/Controllers.
     *
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function buildController()
    {
        $controllerPath = $this->_getControllerPath($this->name);

        if ($this->files->exists($controllerPath) && $this->ask('Already exist Controller. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Controller ...');

        $replace = $this->buildReplacements();

        $controllerTemplate = str_replace(
            array_keys($replace), array_values($replace), $this->getStub('Controller')
        );

        $this->write($controllerPath, $controllerTemplate);

        return $this;
    }

    /**
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function buildModel()
    {
        $modelPath = $this->_getModelPath($this->name);

        if ($this->files->exists($modelPath) && $this->ask('Already exist Model. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Model ...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());

        $modelTemplate = str_replace(
            array_keys($replace), array_values($replace), $this->getStub('Model')
        );

        $this->write($modelPath, $modelTemplate);

        return $this;
    }

    /**
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @throws \Exception
     */
    protected function buildViews()
    {
        $this->info('Creating Views ...');

        $tableHead = "\n";
        $tableBody = "\n";
        $viewRows = "\n";
        $form = "\n";

        foreach ($this->getFilteredColumns() as $column) {
            $title = Str::title(str_replace('_', ' ', $column));

            $tableHead .= $this->getHead($title);
            $tableBody .= $this->getBody($column);
            $viewRows .= $this->getField($title, $column, 'view-field');
            $form .= $this->getField($title, $column, 'form-field');
        }

        $replace = array_merge($this->buildReplacements(), [
            '{{tableHeader}}' => $tableHead,
            '{{tableBody}}' => $tableBody,
            '{{viewRows}}' => $viewRows,
            '{{form}}' => $form,
        ]);

        $this->buildLayout();

        foreach (['index', 'create', 'edit', 'form', 'show'] as $view) {
            $viewTemplate = str_replace(
                array_keys($replace), array_values($replace), $this->getStub("views/{$view}")
            );

            $this->write($this->_getViewPath($view), $viewTemplate);
        }

        return $this;
    }

    protected function generateRoutes()
    {
        $route =$this->options['route'] ?? Str::kebab(Str::plural($this->name));
        $routePath = base_path('routes/web.php');
        $routeContent = File::get($routePath);

        // Define the route string to check and add
        $routeString = "Route::resource('" . strtolower($route) . "', App\Http\Controllers\\" . $this->name . "Controller::class);";

        // Check if the route string already exists in web.php
        if (strpos($routeContent, $routeString) === false) {
            // If not, append the route string to the file
            File::append($routePath, "\n" . $routeString);
            $this->info('Route added successfully.');
        } else {
            $this->info('Route already exists.');
        }
    }

    /**
     * Make the class name from table name.
     *
     * @return string
     */
    private function _buildClassName()
    {
        return Str::studly(Str::singular($this->table));
    }
}
