<?php

namespace Eav\Migrations;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Config\Repository as Config;

class EntityMigrationCreator
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The registered post create hooks.
     *
     * @var array
     */
    protected $postCreate = [];

    /**
     * Create a new migration creator instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem        $files
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(Filesystem $files, Config $config)
    {
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Create a new migration at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    public function create($name, $orgPath, $class)
    {
        $path = $this->getMainPath($name, $orgPath);
        
        $stub = $this->getMainStub();

        $this->files->put($path, $this->populateStub($name, $stub, $class, 'Main'));
        
        sleep(2);
        
        $path = $this->getPath($name, $orgPath);

        $stub = $this->getStub();

        $this->files->put($path, $this->populateStub($name, $stub, $class));
        

        $this->firePostCreateHooks();

        return $path;
    }

    /**
     * Get the migration stub file.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->files->get($this->getStubPath()."/create.entity.stub");
    }
    
    /**
     * Get the migration stub file.
     *
     * @return string
     */
    protected function getMainStub()
    {
        return $this->files->get($this->getStubPath()."/create.entity.main.stub");
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @param  string  $table
     * @return string
     */
    protected function populateStub($name, $stub, $class, $suffix='')
    {
        $stub = $this->addUpMigration($this->getAttributeUpStub(), $stub);

        $stub = $this->addDownMigration($this->getAttributeDownStub(), $stub);

        $stub = str_replace('DummyClass', $this->getClassName($name, $suffix), $stub);

        $stub = str_replace('DummyTable', $name, $stub);
        
        $stub = str_replace('DummyBaseClass', $class, $stub);

        return $stub;
    }


    /**
     * Get the Attribute up migration stub file.
     *
     * @param  string  $attributeStub
     * @param  string  $mainStub
     * @return string
     */
    protected function addUpMigration($attributeStub, $mainStub)
    {
        $fieldTypes = $this->config->get('eav.fieldTypes', []);

        $stub = '';
        foreach ($fieldTypes as $type) {
            $stub .= str_replace('FIELDTYPE', strtolower($type), $attributeStub);
        }

        return str_replace('UPMIGRATION', $stub, $mainStub);
    }

    /**
     * Get the Attribute down migration stub file.
     *
     * @param  string  $attributeStub
     * @param  string  $mainStub
     * @return string
     */
    protected function addDownMigration($attributeStub, $mainStub)
    {
        $fieldTypes = $this->config->get('eav.fieldTypes', []);

        $stub = '';
        foreach ($fieldTypes as $type) {
            $stub .= str_replace('FIELDTYPE',strtolower($type), $attributeStub);
        }
        
        return str_replace('DOWNMIGRATION', $stub, $mainStub);
    }


    /**
     * Get the Attribute up migration stub file.
     * @return string
     */
    protected function getAttributeUpStub()
    {
        return $this->files->get($this->getStubPath()."/attribute.type.up.migration.stub");
    }

    /**
     * Get the Attribute down migration stub file.
     * @return string
     */
    protected function getAttributeDownStub()
    {
        return $this->files->get($this->getStubPath()."/attribute.type.down.migration.stub");
    }

    /**
     * Get the class name of a migration name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name, $suffix)
    {
        return 'Create'.Str::studly($name).'Entity'.$suffix.'Table';
    }

    /**
     * Fire the registered post create hooks.
     *
     * @return void
     */
    protected function firePostCreateHooks()
    {
        foreach ($this->postCreate as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Register a post migration create hook.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function afterCreate(Closure $callback)
    {
        $this->postCreate[] = $callback;
    }

    /**
     * Get the full path name to the migration.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path.'/'.$this->getDatePrefix().'_create_'.$name.'_entity_table.php';
    }
    
    /**
     * Get the full path name to the migration.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getMainPath($name, $path)
    {
        return $path.'/'.$this->getDatePrefix().'_create_'.$name.'_entity_main_table.php';
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function getStubPath()
    {
        return __DIR__.'/stubs';
    }

    /**
     * Get the filesystem instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
