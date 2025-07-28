<?php

namespace SimpleMDB\DatabaseObjects;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Exceptions\SchemaException;

/**
 * Unified manager for all database objects (functions, procedures, views, events, triggers)
 */
class DatabaseObjectManager
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Create a function builder
     */
    public function function(string $functionName): FunctionBuilder
    {
        return new FunctionBuilder($this->db, $functionName);
    }

    /**
     * Create a procedure builder
     */
    public function procedure(string $procedureName): ProcedureBuilder
    {
        return new ProcedureBuilder($this->db, $procedureName);
    }

    /**
     * Create a view builder
     */
    public function view(string $viewName): ViewBuilder
    {
        return new ViewBuilder($this->db, $viewName);
    }

    /**
     * Create an event builder
     */
    public function event(string $eventName): EventBuilder
    {
        return new EventBuilder($this->db, $eventName);
    }

    /**
     * Create a trigger builder
     */
    public function trigger(string $triggerName): TriggerBuilder
    {
        return new TriggerBuilder($this->db, $triggerName);
    }

    /**
     * Get all functions in the database
     */
    public function getFunctions(): array
    {
        $sql = "SELECT * FROM information_schema.routines 
                WHERE routine_type = 'FUNCTION' 
                AND routine_schema = DATABASE()
                ORDER BY routine_name";
        
        try {
            $result = $this->db->query($sql);
            return $result->fetchAll('assoc');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all procedures in the database
     */
    public function getProcedures(): array
    {
        $sql = "SELECT * FROM information_schema.routines 
                WHERE routine_type = 'PROCEDURE' 
                AND routine_schema = DATABASE()
                ORDER BY routine_name";
        
        try {
            $result = $this->db->query($sql);
            return $result->fetchAll('assoc');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all views in the database
     */
    public function getViews(): array
    {
        $sql = "SELECT * FROM information_schema.views 
                WHERE table_schema = DATABASE()
                ORDER BY table_name";
        
        try {
            $result = $this->db->query($sql);
            return $result->fetchAll('assoc');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all events in the database
     */
    public function getEvents(): array
    {
        $sql = "SELECT * FROM information_schema.events 
                WHERE event_schema = DATABASE()
                ORDER BY event_name";
        
        try {
            $result = $this->db->query($sql);
            return $result->fetchAll('assoc');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all triggers in the database
     */
    public function getTriggers(): array
    {
        $sql = "SELECT * FROM information_schema.triggers 
                WHERE trigger_schema = DATABASE()
                ORDER BY trigger_name";
        
        try {
            $result = $this->db->query($sql);
            return $result->fetchAll('assoc');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all database objects (functions, procedures, views, events, triggers)
     */
    public function getAllObjects(): array
    {
        return [
            'functions' => $this->getFunctions(),
            'procedures' => $this->getProcedures(),
            'views' => $this->getViews(),
            'events' => $this->getEvents(),
            'triggers' => $this->getTriggers()
        ];
    }

    /**
     * Drop all objects of a specific type
     */
    public function dropAllFunctions(): bool
    {
        $functions = $this->getFunctions();
        foreach ($functions as $function) {
            $this->function($function['routine_name'])->drop();
        }
        return true;
    }

    public function dropAllProcedures(): bool
    {
        $procedures = $this->getProcedures();
        foreach ($procedures as $procedure) {
            $this->procedure($procedure['routine_name'])->drop();
        }
        return true;
    }

    public function dropAllViews(): bool
    {
        $views = $this->getViews();
        foreach ($views as $view) {
            $this->view($view['table_name'])->drop();
        }
        return true;
    }

    public function dropAllEvents(): bool
    {
        $events = $this->getEvents();
        foreach ($events as $event) {
            $this->event($event['event_name'])->drop();
        }
        return true;
    }

    public function dropAllTriggers(): bool
    {
        $triggers = $this->getTriggers();
        foreach ($triggers as $trigger) {
            $this->trigger($trigger['trigger_name'])->drop();
        }
        return true;
    }

    /**
     * Drop all database objects
     */
    public function dropAllObjects(): bool
    {
        $this->dropAllTriggers();
        $this->dropAllViews();
        $this->dropAllEvents();
        $this->dropAllProcedures();
        $this->dropAllFunctions();
        return true;
    }

    /**
     * Check if any objects exist
     */
    public function hasObjects(): bool
    {
        $objects = $this->getAllObjects();
        foreach ($objects as $type => $list) {
            if (!empty($list)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get object count by type
     */
    public function getObjectCounts(): array
    {
        $objects = $this->getAllObjects();
        $counts = [];
        foreach ($objects as $type => $list) {
            $counts[$type] = count($list);
        }
        return $counts;
    }
} 