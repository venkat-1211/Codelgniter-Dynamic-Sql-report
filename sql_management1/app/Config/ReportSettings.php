<?php
// app/Config/ReportSettings.php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ReportSettings extends BaseConfig
{
    // Available tables for reporting with complex relationships
    public array $availableTables = [
        'joborders' => [
            'name' => 'Job Orders',
            'description' => 'Job orders with candidate assignments',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'candidate_id' => ['type' => 'int', 'label' => 'Candidate ID', 'join' => 'candidates'],
                'client_id' => ['type' => 'int', 'label' => 'Client ID', 'join' => 'client'],
                'site_id' => ['type' => 'int', 'label' => 'Site ID', 'join' => 'site'],
                'agency_id' => ['type' => 'int', 'label' => 'Agency ID', 'join' => 'agency'],
                'start_date' => ['type' => 'date', 'label' => 'Start Date'],
                'end_date' => ['type' => 'date', 'label' => 'End Date'],
                'job_status' => ['type' => 'string', 'label' => 'Job Status'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'candidates' => 'joborders.candidate_id = candidates.id',
                'client' => 'joborders.client_id = client.id',
                'site' => 'joborders.site_id = site.id',
                'agency' => 'joborders.agency_id = agency.id',
            ]
        ],
        'candidates' => [
            'name' => 'Candidates',
            'description' => 'Candidate information',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'first_name' => ['type' => 'string', 'label' => 'First Name'],
                'last_name' => ['type' => 'string', 'label' => 'Last Name'],
                'email' => ['type' => 'string', 'label' => 'Email'],
                'phone' => ['type' => 'string', 'label' => 'Phone'],
                'registration_office' => ['type' => 'int', 'label' => 'Registration Office', 'join' => 'office_locations'],
                'client_associated' => ['type' => 'text', 'label' => 'Client Associated'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'candidates_additional' => 'candidates.id = candidates_additional.candidate_id',
                'home_application' => 'candidates.id = home_application.candidate_id',
                'candidates_tags' => 'candidates.id = candidates_tags.candidate_id',
                'office_locations' => 'candidates.registration_office = office_locations.id',
            ]
        ],
        'candidates_additional' => [
            'name' => 'Candidate Additional Info',
            'description' => 'Additional candidate details',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'candidate_id' => ['type' => 'int', 'label' => 'Candidate ID', 'join' => 'candidates'],
                'active_candidate' => ['type' => 'string', 'label' => 'Active Candidate'],
                'onhold_status' => ['type' => 'int', 'label' => 'On Hold Status'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'candidates' => 'candidates_additional.candidate_id = candidates.id'
            ]
        ],
        'client' => [
            'name' => 'Clients',
            'description' => 'Client information',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'client_name' => ['type' => 'string', 'label' => 'Client Name'],
                'client_code' => ['type' => 'string', 'label' => 'Client Code'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ]
        ],
        'site' => [
            'name' => 'Sites',
            'description' => 'Site information',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'site_name' => ['type' => 'string', 'label' => 'Site Name'],
                'client_id' => ['type' => 'int', 'label' => 'Client ID', 'join' => 'client'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'client' => 'site.client_id = client.id'
            ]
        ],
        'agency' => [
            'name' => 'Agencies',
            'description' => 'Agency information',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'agency_name' => ['type' => 'string', 'label' => 'Agency Name'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ]
        ],
        'timesheets' => [
            'name' => 'Timesheets',
            'description' => 'Timesheet records',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'job_id' => ['type' => 'int', 'label' => 'Job ID', 'join' => 'joborders'],
                'start_date_time' => ['type' => 'datetime', 'label' => 'Start DateTime'],
                'end_date_time' => ['type' => 'datetime', 'label' => 'End DateTime'],
                'hours_worked' => ['type' => 'decimal', 'label' => 'Hours Worked'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'joborders' => 'timesheets.job_id = joborders.id'
            ]
        ],
        'roles' => [
            'name' => 'Roles',
            'description' => 'Job roles',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'role_name' => ['type' => 'string', 'label' => 'Role Name'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ]
        ],
        'office_locations' => [
            'name' => 'Office Locations',
            'description' => 'Office location details',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'location' => ['type' => 'string', 'label' => 'Location'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ]
        ],
        'home_application' => [
            'name' => 'Home Applications',
            'description' => 'Candidate home applications',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'candidate_id' => ['type' => 'int', 'label' => 'Candidate ID', 'join' => 'candidates'],
                'skills' => ['type' => 'text', 'label' => 'Skills'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'candidates' => 'home_application.candidate_id = candidates.id'
            ]
        ],
        'candidates_tags' => [
            'name' => 'Candidate Tags',
            'description' => 'Candidate tags/categories',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'candidate_id' => ['type' => 'int', 'label' => 'Candidate ID', 'join' => 'candidates'],
                'tag_name' => ['type' => 'string', 'label' => 'Tag Name'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'candidates' => 'candidates_tags.candidate_id = candidates.id'
            ]
        ],
        'candidates_qualification' => [
            'name' => 'Candidate Qualifications',
            'description' => 'Candidate qualification details',
            'fields' => [
                'id' => ['type' => 'int', 'label' => 'ID'],
                'candidate_id' => ['type' => 'int', 'label' => 'Candidate ID', 'join' => 'candidates'],
                'file_name' => ['type' => 'string', 'label' => 'File Name'],
                'expire_date' => ['type' => 'date', 'label' => 'Expire Date'],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'joins' => [
                'candidates' => 'candidates_qualification.candidate_id = candidates.id'
            ]
        ]
    ];

    // Join types
    public array $joinTypes = [
        'INNER' => 'INNER JOIN',
        'LEFT' => 'LEFT JOIN',
        'RIGHT' => 'RIGHT JOIN',
        'FULL' => 'FULL OUTER JOIN'
    ];

    // Complex functions for calculated fields
    public array $complexFunctions = [
        'CONCAT' => 'Concatenate strings',
        'CONCAT_WS' => 'Concatenate with separator',
        'DATE_FORMAT' => 'Format date',
        'TIMESTAMPDIFF' => 'Time difference',
        'ROUND' => 'Round number',
        'SUM' => 'Sum',
        'AVG' => 'Average',
        'COUNT' => 'Count',
        'MIN' => 'Minimum',
        'MAX' => 'Maximum',
        'CASE' => 'Case statement',
        'IF' => 'If statement',
        'IFNULL' => 'If null',
        'NULLIF' => 'Null if equal',
        'FIND_IN_SET' => 'Find in set',
        'UPPER' => 'Uppercase',
        'LOWER' => 'Lowercase',
        'TRIM' => 'Trim string',
        'GROUP_CONCAT' => 'Group concatenate'
    ];

    // Predefined report templates based on your examples
    public array $reportTemplates = [
        'attendance_report' => [
            'name' => 'Candidate Attendance Report',
            'description' => 'Attendance percentage with shift details',
            'sql_template' => "SELECT candidate_id,candidate_name,client_site_agency,shift_period,total_shifts,attended_shifts,not_attended_shifts,ROUND((attended_shifts / NULLIF(total_shifts, 0)) * 100,2) AS attendance_percentage,candidate_status FROM (

    SELECT jo.candidate_id,CONCAT(UPPER(can.last_name),' ',can.first_name) candidate_name,CONCAT_WS(' - ',cl.client_name,si.site_name,NULLIF(NULLIF(TRIM(ag.agency_name), ''), '-')) client_site_agency,DATE_FORMAT(lat.max_start_date, '%p') AS shift_period,

(SELECT count(tjo.id) FROM joborders tjo WHERE jo.client_id=tjo.client_id AND jo.candidate_id=tjo.candidate_id AND tjo.job_status!='' AND DATE(tjo.start_date)>=:start_date AND DATE(tjo.start_date)<=:end_date) total_shifts,

(SELECT count(ajo.id) FROM joborders ajo WHERE jo.client_id=ajo.client_id AND jo.candidate_id=ajo.candidate_id AND ajo.job_status!='' AND LOWER(ajo.job_status) IN ('filled','closed') AND DATE(ajo.start_date)>=:start_date AND DATE(ajo.start_date)<=:end_date) attended_shifts,

(SELECT count(njo.id) FROM joborders njo WHERE jo.client_id=njo.client_id AND jo.candidate_id=njo.candidate_id AND njo.job_status!='' AND LOWER(njo.job_status) NOT IN ('filled','closed') AND DATE(njo.start_date)>=:start_date AND DATE(njo.start_date)<=:end_date ) not_attended_shifts,

CASE 

        WHEN LOWER(cad.active_candidate) = 'yes' AND cad.onhold_status = 1 THEN 'Active/On-hold'

        WHEN LOWER(cad.active_candidate) = 'yes' AND cad.onhold_status != 1 THEN 'Active'

        WHEN LOWER(cad.active_candidate) = 'no' THEN 'Discontinued'

        WHEN LOWER(cad.active_candidate) = 'fld' THEN 'Filled'

        WHEN LOWER(cad.active_candidate) = 'no-ntr' THEN 'Discontinued - NTR'

        WHEN LOWER(cad.active_candidate) = 'no-fit' THEN 'Discontinued - Failed Fitness'

        WHEN LOWER(cad.active_candidate) = 'pdg' THEN 'Pending'

        ELSE ''

    END AS candidate_status

FROM joborders jo

JOIN client cl on cl.id=jo.client_id

JOIN site si on si.id=jo.site_id

JOIN agency ag on ag.id=jo.agency_id

JOIN candidates can on can.id=jo.candidate_id

JOIN candidates_additional cad on can.id=cad.candidate_id

JOIN (

SELECT lat.candidate_id,MAX(lat.start_date) max_start_date FROM joborders lat WHERE lat.client_id=:client_id AND DATE(lat.start_date)>=:start_date AND DATE(lat.start_date)<=:end_date

AND LOWER(lat.job_status) IN ('filled','closed') GROUP BY lat.candidate_id

) lat on lat.candidate_id=jo.candidate_id

WHERE cl.id=:client_id AND DATE(jo.start_date)>=:start_date AND DATE(jo.start_date)<=:end_date

GROUP BY jo.candidate_id ORDER BY can.last_name,can.first_name) as tbl",
            'parameters' => [
                'client_id' => ['type' => 'int', 'label' => 'Client ID', 'required' => true],
                'start_date' => ['type' => 'date', 'label' => 'Start Date', 'required' => true],
                'end_date' => ['type' => 'date', 'label' => 'End Date', 'required' => true]
            ]
        ],
        'candidate_license_report' => [
            'name' => 'Candidate License Report',
            'description' => 'License status with office locations',
            'sql_template' => "SELECT ha.candidate_id,CONCAT(UPPER(can.last_name),' ',can.first_name) candiate_name,ofl.location as office_location,
CASE
    WHEN cq.file_name IS NOT NULL
         AND cq.file_name!=''
        THEN 'Yes'
    ELSE 'No'
END AS have_license,CASE
        WHEN cq.file_name IS NOT NULL
             AND cq.file_name!=''
            THEN cq.expire_date
        ELSE ''
    END AS expire_date,CASE LOWER(cad.active_candidate)
        WHEN 'yes' THEN 
            IF(cad.onhold_status = 1, 'Active/On Hold', 'Active')
        WHEN 'fld' THEN 'Filled'
    END AS candidate_status
            FROM candidates can
JOIN candidates_additional cad on can.id=cad.candidate_id
JOIN home_application ha on can.id=ha.candidate_id
JOIN candidates_tags ct on can.id=ct.candidate_id
JOIN office_locations ofl on ofl.id=can.registration_office
LEFT JOIN candidates_qualification cq on can.id=cq.candidate_id
WHERE can.registration_office IN (:office_ids) AND LOWER(cad.active_candidate) IN ('yes')
AND ha.skills LIKE CONCAT('%', :skill_id, '%')
GROUP BY can.id ORDER BY can.last_name",
            'parameters' => [
                'office_ids' => ['type' => 'array', 'label' => 'Office IDs', 'required' => true],
                'skill_id' => ['type' => 'int', 'label' => 'Skill ID', 'required' => true]
            ]
        ],
        'timesheet_hours_report' => [
            'name' => 'Timesheet Hours Report',
            'description' => 'Total hours worked by agency',
            'sql_template' => "SELECT jo.candidate_id,CONCAT(UPPER(can.last_name),' ',can.first_name) candidate_name,CONCAT_WS(' - ',cl.client_name,si.site_name,ag.agency_name) client_site_agency,ROUND(
  SUM(TIMESTAMPDIFF(MINUTE, ts.start_date_time, ts.end_date_time)) / 60,
  2
) AS total_hours
 FROM joborders jo 
JOIN timesheets ts on jo.id=ts.job_id
JOIN candidates can on can.id=jo.candidate_id
JOIN client cl on cl.id=jo.client_id
JOIN site si on si.id=jo.site_id
JOIN agency ag on ag.id=jo.agency_id
WHERE jo.agency_id=:agency_id
AND  LOWER(jo.job_status) IN ('filled','closed')
GROUP BY can.id",
            'parameters' => [
                'agency_id' => ['type' => 'int', 'label' => 'Agency ID', 'required' => true]
            ]
        ]
    ];

    // Export settings
    public array $exportSettings = [
        'xlsx' => [
            'enabled' => true,
            'max_rows' => 100000,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension' => 'xlsx'
        ],
        'csv' => [
            'enabled' => true,
            'max_rows' => 500000,
            'mime_type' => 'text/csv',
            'extension' => 'csv'
        ]
    ];
}