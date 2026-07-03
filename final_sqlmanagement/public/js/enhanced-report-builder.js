class EnhancedReportBuilder {
    constructor() {
        this.columnCounter = 0;
        this.joinCounter = 0;
        this.conditionCounter = 0;
        this.orderCounter = 0;
        this.groupCounter = 0;
        this.havingCounter = 0;
        this.caseCounter = 0;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadComplexOptions();
    }
    
    bindEvents() {
        // Enhanced order by controls
        $(document).on('change', '.order-type-select', this.handleOrderTypeChange.bind(this));
        $(document).on('change', '.group-type-select', this.handleGroupTypeChange.bind(this));
        
        // Complex expression builders
        $(document).on('click', '.build-expression', this.showExpressionBuilder.bind(this));
        $(document).on('click', '.build-case', this.showCaseBuilder.bind(this));
        $(document).on('click', '.build-window', this.showWindowFunctionBuilder.bind(this));
        
        // Parameter binding
        $(document).on('click', '.bind-parameter', this.bindParameter.bind(this));
        
        // Preview enhancements
        $(document).on('click', '#previewComplex', this.previewComplexSql.bind(this));
    }
    
    loadComplexOptions() {
        // Load ORDER BY options
        $.get('/api/reports/order-options', (data) => {
            this.orderOptions = data;
            this.populateOrderOptions();
        });
        
        // Load GROUP BY options
        $.get('/api/reports/group-options', (data) => {
            this.groupOptions = data;
            this.populateGroupOptions();
        });
    }
    
    addComplexOrder() {
        const html = `
            <div class="complex-order-item mb-3 p-3 border rounded">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Order Type</label>
                        <select class="form-control order-type-select" name="orders[${this.orderCounter}][order_type]">
                            <option value="COLUMN">Column</option>
                            <option value="EXPRESSION">Expression</option>
                            <option value="CASE">CASE WHEN</option>
                            <option value="FUNCTION">Function</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Expression</label>
                        <div class="input-group">
                            <input type="text" class="form-control order-expression" 
                                   name="orders[${this.orderCounter}][order_expression]"
                                   placeholder="Column or expression">
                            <button type="button" class="btn btn-outline-secondary build-expression">
                                <i class="fas fa-code"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Direction</label>
                        <select class="form-control" name="orders[${this.orderCounter}][direction]">
                            <option value="ASC">ASC</option>
                            <option value="DESC">DESC</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">NULLs Order</label>
                        <select class="form-control" name="orders[${this.orderCounter}][nulls_order]">
                            <option value="">Default</option>
                            <option value="NULLS FIRST">NULLS FIRST</option>
                            <option value="NULLS LAST">NULLS LAST</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2 order-extra-fields" style="display: none;">
                    <!-- Extra fields for complex order types will be shown here -->
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-danger btn-sm remove-order">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </div>`;
        
        $('#complexOrdersContainer').append(html);
        this.orderCounter++;
    }
    
    addComplexGroup() {
        const html = `
            <div class="complex-group-item mb-3 p-3 border rounded">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Group Type</label>
                        <select class="form-control group-type-select" name="groups[${this.groupCounter}][group_type]">
                            <option value="COLUMN">Column</option>
                            <option value="EXPRESSION">Expression</option>
                            <option value="ROLLUP">ROLLUP</option>
                            <option value="CUBE">CUBE</option>
                            <option value="GROUPING_SETS">GROUPING SETS</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Expression</label>
                        <div class="input-group">
                            <input type="text" class="form-control group-expression" 
                                   name="groups[${this.groupCounter}][group_expression]"
                                   placeholder="Column or expression">
                            <button type="button" class="btn btn-outline-secondary build-expression">
                                <i class="fas fa-code"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rollup</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" 
                                   name="groups[${this.groupCounter}][with_rollup]" value="1">
                            <label class="form-check-label">With ROLLUP</label>
                        </div>
                    </div>
                </div>
                <div class="row mt-2 group-extra-fields" style="display: none;">
                    <!-- Extra fields for complex group types will be shown here -->
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-danger btn-sm remove-group">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </div>`;
        
        $('#complexGroupsContainer').append(html);
        this.groupCounter++;
    }
    
    addComplexHaving() {
        const html = `
            <div class="complex-having-item mb-3 p-3 border rounded">
                <div class="row">
                    <div class="col-md-9">
                        <label class="form-label">HAVING Expression</label>
                        <textarea class="form-control having-expression" 
                                  name="having[${this.havingCounter}][having_expression]"
                                  rows="2"
                                  placeholder="Aggregate condition (e.g., SUM(amount) > 1000)"></textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Operator</label>
                        <select class="form-control" name="having[${this.havingCounter}][operator]">
                            <option value="AND">AND</option>
                            <option value="OR">OR</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm w-100 remove-having">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input is-parameter" 
                                   name="having[${this.havingCounter}][is_parameter]" value="1">
                            <label class="form-check-label">Use Parameter</label>
                        </div>
                    </div>
                    <div class="col-md-6 parameter-fields" style="display: none;">
                        <input type="text" class="form-control" 
                               name="having[${this.havingCounter}][parameter_name]"
                               placeholder="Parameter name">
                    </div>
                </div>
            </div>`;
        
        $('#complexHavingContainer').append(html);
        this.havingCounter++;
    }
    
    handleOrderTypeChange(event) {
        const $select = $(event.target);
        const $item = $select.closest('.complex-order-item');
        const orderType = $select.val();
        const $extraFields = $item.find('.order-extra-fields');
        
        let extraHtml = '';
        
        switch(orderType) {
            case 'FUNCTION':
                extraHtml = `
                    <div class="col-md-12">
                        <label class="form-label">Function</label>
                        <select class="form-control order-function">
                            ${this.orderOptions.functions ? Object.entries(this.orderOptions.functions).map(([key, value]) => 
                                `<option value="${value}">${key}</option>`
                            ).join('') : ''}
                        </select>
                    </div>`;
                break;
            case 'CASE':
                extraHtml = `
                    <div class="col-md-12">
                        <label class="form-label">CASE WHEN Builder</label>
                        <button type="button" class="btn btn-outline-primary btn-sm build-case">
                            <i class="fas fa-cogs"></i> Build CASE Expression
                        </button>
                    </div>`;
                break;
        }
        
        $extraFields.html(extraHtml).toggle(extraHtml !== '');
    }
    
    handleGroupTypeChange(event) {
        const $select = $(event.target);
        const $item = $select.closest('.complex-group-item');
        const groupType = $select.val();
        const $extraFields = $item.find('.group-extra-fields');
        
        let extraHtml = '';
        
        switch(groupType) {
            case 'GROUPING_SETS':
                extraHtml = `
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <small>Enter multiple grouping sets separated by semicolons. Example: (department, year); (department); (year)</small>
                        </div>
                    </div>`;
                break;
            case 'EXPRESSION':
                extraHtml = `
                    <div class="col-md-12">
                        <label class="form-label">Expression Type</label>
                        <select class="form-control group-function">
                            ${this.groupOptions.functions ? Object.entries(this.groupOptions.functions).map(([key, value]) => 
                                `<option value="${value}">${key}</option>`
                            ).join('') : ''}
                        </select>
                    </div>`;
                break;
        }
        
        $extraFields.html(extraHtml).toggle(extraHtml !== '');
    }
    
    showExpressionBuilder(event) {
        const $button = $(event.target);
        const $input = $button.closest('.input-group').find('input');
        
        // Show expression builder modal
        $('#expressionBuilderModal').modal('show');
        
        // Store reference to the input field
        $('#expressionBuilderModal').data('targetInput', $input);
    }
    
    showCaseBuilder(event) {
        $('#caseBuilderModal').modal('show');
        
        const $button = $(event.target);
        const $item = $button.closest('.complex-order-item');
        $('#caseBuilderModal').data('targetItem', $item);
    }
    
    buildCaseExpression(cases, elseValue = null) {
        let caseSql = 'CASE';
        
        cases.forEach((caseItem, index) => {
            caseSql += ` WHEN ${caseItem.when} THEN '${caseItem.then}'`;
        });
        
        if (elseValue) {
            caseSql += ` ELSE '${elseValue}'`;
        }
        
        caseSql += ' END';
        
        return caseSql;
    }
    
    bindParameter(event) {
        const $button = $(event.target);
        const $container = $button.closest('.parameter-bindable');
        const $input = $container.find('input, textarea').first();
        
        const currentValue = $input.val();
        const paramName = prompt('Enter parameter name:');
        
        if (paramName) {
            $input.val(currentValue + (currentValue ? ' ' : '') + `:${paramName}`);
        }
    }
    
    previewComplexSql() {
        const formData = this.collectEnhancedFormData();
        
        $.ajax({
            url: '/reports/preview-complex-sql',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: (response) => {
                if (response.status === 'success') {
                    $('#complexSqlPreview').html(`<pre class="p-3 bg-dark text-light">${response.sql}</pre>`);
                    
                    if (response.params && response.params.length > 0) {
                        let paramsHtml = '<div class="mt-3"><h6>Parameters:</h6><ul>';
                        response.params.forEach(param => {
                            paramsHtml += `<li><code>${param.name}</code>: ${param.default || 'No default'}</li>`;
                        });
                        paramsHtml += '</ul></div>';
                        $('#complexSqlPreview').append(paramsHtml);
                    }
                }
            }
        });
    }
    
    collectEnhancedFormData() {
        const data = {
            base_table: $('#baseTable').val(),
            columns: [],
            joins: [],
            conditions: [],
            orders: [],
            groups: [],
            having: [],
            case_mappings: [],
            parameters: {}
        };
        
        // Collect columns
        $('.column-item').each(function() {
            data.columns.push({
                column_expression: $(this).find('[name*="column_expression"]').val(),
                alias: $(this).find('[name*="alias"]').val(),
                column_type: $(this).find('[name*="column_type"]').val()
            });
        });
        
        // Collect complex orders
        $('.complex-order-item').each(function() {
            data.orders.push({
                order_type: $(this).find('[name*="order_type"]').val(),
                order_expression: $(this).find('[name*="order_expression"]').val(),
                direction: $(this).find('[name*="direction"]').val(),
                nulls_order: $(this).find('[name*="nulls_order"]').val() || null
            });
        });
        
        // Collect complex groups
        $('.complex-group-item').each(function() {
            data.groups.push({
                group_type: $(this).find('[name*="group_type"]').val(),
                group_expression: $(this).find('[name*="group_expression"]').val(),
                with_rollup: $(this).find('[name*="with_rollup"]').is(':checked') ? 1 : 0
            });
        });
        
        // Collect HAVING conditions
        $('.complex-having-item').each(function() {
            data.having.push({
                having_expression: $(this).find('[name*="having_expression"]').val(),
                operator: $(this).find('[name*="operator"]').val(),
                is_parameter: $(this).find('.is-parameter').is(':checked') ? 1 : 0,
                parameter_name: $(this).find('[name*="parameter_name"]').val() || null
            });
        });
        
        return data;
    }
}

// Initialize when document is ready
$(document).ready(function() {
    window.reportBuilder = new EnhancedReportBuilder();
});