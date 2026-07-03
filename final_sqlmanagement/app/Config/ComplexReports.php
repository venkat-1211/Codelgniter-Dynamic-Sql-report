<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ComplexReports extends BaseConfig
{
    // Example complex report definitions that can be imported
    
    public $advancedOrderByExamples = [
        'date_with_nulls_last' => [
            'description' => 'Order by date with NULLS LAST',
            'orders' => [
                [
                    'order_type' => 'EXPRESSION',
                    'order_expression' => 'effective_date',
                    'direction' => 'DESC',
                    'nulls_order' => 'NULLS LAST'
                ]
            ]
        ],
        'case_sensitive_sort' => [
            'description' => 'Case-sensitive alphabetical sort',
            'orders' => [
                [
                    'order_type' => 'FUNCTION',
                    'order_expression' => 'BINARY last_name',
                    'direction' => 'ASC'
                ],
                [
                    'order_type' => 'FUNCTION',
                    'order_expression' => 'BINARY first_name',
                    'direction' => 'ASC'
                ]
            ]
        ],
        'custom_priority_order' => [
            'description' => 'Custom priority-based ordering',
            'orders' => [
                [
                    'order_type' => 'CASE',
                    'order_expression' => "CASE 
                        WHEN status = 'URGENT' THEN 1
                        WHEN status = 'HIGH' THEN 2
                        WHEN status = 'MEDIUM' THEN 3
                        WHEN status = 'LOW' THEN 4
                        ELSE 5
                    END",
                    'direction' => 'ASC'
                ],
                [
                    'order_type' => 'EXPRESSION',
                    'order_expression' => 'due_date',
                    'direction' => 'ASC'
                ]
            ]
        ]
    ];
    
    public $advancedGroupByExamples = [
        'monthly_rollup' => [
            'description' => 'Monthly grouping with ROLLUP for totals',
            'groups' => [
                [
                    'group_type' => 'FUNCTION',
                    'group_expression' => 'YEAR(transaction_date)',
                    'with_rollup' => true
                ],
                [
                    'group_type' => 'FUNCTION',
                    'group_expression' => 'MONTH(transaction_date)',
                    'with_rollup' => true
                ]
            ]
        ],
        'multi_level_cube' => [
            'description' => 'Multi-dimensional analysis with CUBE',
            'groups' => [
                [
                    'group_type' => 'CUBE',
                    'group_expression' => 'department_id, category_id, status'
                ]
            ]
        ],
        'conditional_grouping' => [
            'description' => 'Group by conditional categories',
            'groups' => [
                [
                    'group_type' => 'EXPRESSION',
                    'group_expression' => "CASE 
                        WHEN amount < 1000 THEN 'Small'
                        WHEN amount BETWEEN 1000 AND 10000 THEN 'Medium'
                        ELSE 'Large'
                    END"
                ]
            ]
        ]
    ];
    
    public $complexHavingExamples = [
        'aggregate_filter' => [
            'description' => 'Filter groups based on aggregate values',
            'having' => [
                [
                    'having_expression' => 'COUNT(*) > 10',
                    'operator' => 'AND'
                ],
                [
                    'having_expression' => 'AVG(amount) > 1000',
                    'operator' => 'AND'
                ]
            ]
        ],
        'complex_aggregate' => [
            'description' => 'Complex aggregate conditions',
            'having' => [
                [
                    'having_expression' => 'SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) > 10000',
                    'operator' => 'AND'
                ]
            ]
        ]
    ];
    
    /**
     * Get example complex report
     */
    public function getComplexExample($name)
    {
        $examples = [
            'candidate_analytics' => [
                'report_name' => 'Advanced Candidate Analytics',
                'base_table' => 'candidates',
                'description' => 'Complex candidate analysis with window functions',
                'columns' => [
                    [
                        'column_expression' => 'c.id',
                        'alias' => 'CandidateID'
                    ],
                    [
                        'column_expression' => 'CONCAT(c.last_name, ", ", c.first_name)',
                        'alias' => 'FullName'
                    ],
                    [
                        'column_expression' => 'ROW_NUMBER() OVER (PARTITION BY c.department ORDER BY c.hire_date)',
                        'alias' => 'DeptSeniorityRank'
                    ],
                    [
                        'column_expression' => 'DENSE_RANK() OVER (ORDER BY cs.salary DESC)',
                        'alias' => 'SalaryRank'
                    ],
                    [
                        'column_expression' => 'AVG(cs.salary) OVER (PARTITION BY c.department)',
                        'alias' => 'DeptAvgSalary'
                    ],
                    [
                        'column_expression' => 'cs.salary - AVG(cs.salary) OVER (PARTITION BY c.department)',
                        'alias' => 'SalaryVsDeptAvg'
                    ]
                ],
                'joins' => [
                    [
                        'join_type' => 'LEFT',
                        'table_name' => 'candidate_salaries',
                        'alias' => 'cs',
                        'join_condition' => 'c.id = cs.candidate_id AND cs.is_current = 1'
                    ],
                    [
                        'join_type' => 'LEFT',
                        'table_name' => 'departments',
                        'alias' => 'd',
                        'join_condition' => 'c.department_id = d.id'
                    ]
                ],
                'orders' => [
                    [
                        'order_type' => 'EXPRESSION',
                        'order_expression' => 'SalaryRank',
                        'direction' => 'ASC'
                    ]
                ],
                'groups' => [
                    [
                        'group_type' => 'COLUMN',
                        'group_expression' => 'c.id'
                    ]
                ]
            ]
        ];
        
        return $examples[$name] ?? null;
    }
}