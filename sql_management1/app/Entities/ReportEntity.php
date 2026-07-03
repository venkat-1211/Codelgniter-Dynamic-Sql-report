<?php
// app/Entities/ReportEntity.php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ReportEntity extends Entity
{
    protected $attributes = [
        'id' => null,
        'report_name' => null,
        'description' => null,
        'report_type' => 'simple',
        'base_table' => null,
        'base_tables' => null,
        'joins_config' => null,
        'columns_config' => null,
        'calculated_fields' => null,
        'filters_config' => null,
        'grouping_config' => null,
        'sorting_config' => null,
        'subqueries_config' => null,
        'custom_sql' => null,
        'access_roles' => null,
        'export_formats' => null,
        'created_by' => null,
        'is_active' => 1,
        'created_at' => null,
        'updated_at' => null
    ];

    protected $dates = ['created_at', 'updated_at'];
    
    protected $casts = [
        'id' => 'integer',
        'created_by' => 'integer',
        'is_active' => 'boolean'
    ];

    // Get decoded JSON configs
    public function getBaseTables(): array
    {
        return json_decode($this->attributes['base_tables'] ?? '[]', true) ?: [];
    }

    public function getJoinsConfig(): array
    {
        return json_decode($this->attributes['joins_config'] ?? '[]', true) ?: [];
    }

    public function getColumnsConfig(): array
    {
        return json_decode($this->attributes['columns_config'] ?? '[]', true) ?: [];
    }

    public function getCalculatedFields(): array
    {
        return json_decode($this->attributes['calculated_fields'] ?? '[]', true) ?: [];
    }

    public function getFiltersConfig(): array
    {
        return json_decode($this->attributes['filters_config'] ?? '[]', true) ?: [];
    }

    public function getGroupingConfig(): array
    {
        return json_decode($this->attributes['grouping_config'] ?? '[]', true) ?: [];
    }

    public function getSortingConfig(): array
    {
        return json_decode($this->attributes['sorting_config'] ?? '[]', true) ?: [];
    }

    public function getSubqueriesConfig(): array
    {
        return json_decode($this->attributes['subqueries_config'] ?? '[]', true) ?: [];
    }

    public function getAccessRoles(): array
    {
        return json_decode($this->attributes['access_roles'] ?? '[]', true) ?: [];
    }

    public function getExportFormats(): array
    {
        return json_decode($this->attributes['export_formats'] ?? '["xlsx","csv"]', true) ?: ['xlsx', 'csv'];
    }

    // Set encoded JSON configs
    public function setBaseTables(array $value)
    {
        $this->attributes['base_tables'] = json_encode($value);
        return $this;
    }

    public function setJoinsConfig(array $value)
    {
        $this->attributes['joins_config'] = json_encode($value);
        return $this;
    }

    public function setColumnsConfig(array $value)
    {
        $this->attributes['columns_config'] = json_encode($value);
        return $this;
    }

    public function setCalculatedFields(array $value)
    {
        $this->attributes['calculated_fields'] = json_encode($value);
        return $this;
    }

    public function setFiltersConfig(array $value)
    {
        $this->attributes['filters_config'] = json_encode($value);
        return $this;
    }

    public function setGroupingConfig(array $value)
    {
        $this->attributes['grouping_config'] = json_encode($value);
        return $this;
    }

    public function setSortingConfig(array $value)
    {
        $this->attributes['sorting_config'] = json_encode($value);
        return $this;
    }

    public function setSubqueriesConfig(array $value)
    {
        $this->attributes['subqueries_config'] = json_encode($value);
        return $this;
    }

    public function setAccessRoles(array $value)
    {
        $this->attributes['access_roles'] = json_encode($value);
        return $this;
    }

    public function setExportFormats(array $value)
    {
        $this->attributes['export_formats'] = json_encode($value);
        return $this;
    }

    // Check if user can access
    public function canAccess(string $role): bool
    {
        $roles = $this->getAccessRoles();
        return empty($roles) || in_array($role, $roles);
    }

    // Check if format is allowed
    public function canExport(string $format): bool
    {
        $formats = $this->getExportFormats();
        return in_array($format, $formats);
    }

    // Check if report has custom SQL
    public function hasCustomSQL(): bool
    {
        return !empty($this->attributes['custom_sql']);
    }

    // Get custom SQL
    public function getCustomSQL(): string
    {
        return $this->attributes['custom_sql'] ?? '';
    }
}