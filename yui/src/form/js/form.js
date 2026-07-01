// eslint-disable-next-line camelcase
M.availability_quizgradeitem = M.availability_quizgradeitem || {};

M.availability_quizgradeitem.form = Y.Object(M.core_availability.plugin);

/**
 * Quizzes available for selection (alphabetical order).
 *
 * @property quizzes
 * @type Array
 */

M.availability_quizgradeitem.form.quizzes = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} quizzes Array of objects containing quiz .id and .name
 */
M.availability_quizgradeitem.form.initInner = function(quizzes) {
    this.quizzes = quizzes;
    window.console.log(quizzes);
};

M.availability_quizgradeitem.form.getNode = function(json) {
    // Init variables.
    var i;
    var html = '<br>';

    // Get strings
    var title = M.util.get_string('title', 'availability_quizgradeitem');
    var labelGradeItem = M.util.get_string('label_gradeitem', 'availability_quizgradeitem');
    var chooseDots = M.util.get_string('choosedots', 'moodle');
    var optionMin = M.util.get_string('option_min', 'availability_quizgradeitem');
    var labelMin = M.util.get_string('label_min', 'availability_quizgradeitem');
    var optionMax = M.util.get_string('option_max', 'availability_quizgradeitem');
    var labelMax = M.util.get_string('label_max', 'availability_quizgradeitem');

    // Quizzes
    html += '<label class="mb-3"><span class="pe-3">' + title + '</span> ' +
                '<span class="availability-group">' +
                '<select name="quizid" class="custom-select">' +
                    '<option value="0">' + chooseDots + '</option>';
    for (i = 0; i < this.quizzes.length; i++) {
        // String has already been escaped using format_string.
        html += '<option value="' + this.quizzes[i].id + '">' + this.quizzes[i].name + '</option>';
    }
    html += '</select></span></label><br>';

    // Grading item
    html += '<label class="mb-3"><span class="pe-3">' + labelGradeItem + '</span> ' +
                '<span class="availability-group">' +
                '<select name="grateitemid" class="custom-select">' +
                    '<option value="0">' + chooseDots + '</option>';
    html += '</select></span></label><br>';

    // Min
    html += '<span class="availability-group mb-3">' +
                '<label><input type="checkbox" class="form-check-input position-static mt-0 mx-1" name="max"/>' +
                    optionMin +
                '</label>' +
                '<label>' +
                    '<span class="accesshide">' + labelMin + '</span>' +
                    '<input type="text" class="form-control mx-1" name="minval" title="' + labelMin + '"/>' +
                '</label>' +
            ' %</span>' +
            '<br>';

    // Max
    html += '<span class="availability-group mb-3">' +
                '<label><input type="checkbox" class="form-check-input position-static mt-0 mx-1" name="max"/>' +
                    optionMax +
                '</label>' +
                '<label>' +
                    '<span class="accesshide">' + labelMax + '</span>' +
                    '<input type="text" class="form-control mx-1" name="minval" title="' + labelMax + '"/>' +
                '</label>' +
            ' %</span>' +
            '<br>';

    var node = Y.Node.create('<div class="d-inline-block d-flex flex-column w-100">' + html + '</div>');

    var updateGradeItems = function(quizNode, gradeItemNode, callback) {
        var quizId = quizNode.get('value');

        // Remove the existing options.
        gradeItemNode.all('option').each(function(optionNode) {
            if (optionNode.get('value') !== '') {
                optionNode.remove();
            }
        }, this);

        // Disable the quiz element until we finish loading its grade items.
        if (quizId) {
            quizNode.set('disabled', true);
            var pendingKey = {};

            require(['core/ajax', 'core/notification'], function(ajax, notification) {
                ajax.call([{
                    methodname: 'mod_quiz_get_edit_grading_page_data',
                    args: {
                        quizid: quizId,
                    }
                }])[0]
                    .then(function(results) {
                        var gradeItems = results.gradeitems;
                        for (var i = 0; i < gradeItems.length; i++) {
                            var gradeItemOption = document.createElement('option');
                            gradeItemOption.value = gradeItems[i].id;
                            gradeItemOption.innerHTML = gradeItems[i].displayname;
                            gradeItemNode.append(gradeItemOption);
                        }
                        // Questions are loaded, so we enable the quiz element now.
                        quizNode.set('disabled', false);

                        if (callback !== undefined) {
                            callback();
                        }

                        M.core_availability.form.update();
                        M.util.js_complete(pendingKey);
                    }).catch(notification.exception);
            });
        }
    };

    // Set initial value if specified.
    if (json.quizid !== undefined &&
            node.one('select[name=quizid] > option[value=' + json.quizid + ']')) {
        node.one('select[name=quizid]').set('value', '' + json.quizid);
        updateGradeItems(node.one('select[name=quizid]'), node.one('select[name=questionbankentryid]'), function() {
            if (json.questionbankentryid !== undefined &&
                node.one('select[name=questionbankentryid] > option[value=' + json.questionbankentryid + ']')) {
                node.one('select[name=questionbankentryid]').set('value', '' + json.questionbankentryid);
            }
        });
    }
    if (json.requiredstate !== undefined &&
            node.one('select[name=requiredstate] > option[value=' + json.requiredstate + ']')) {
        node.one('select[name=requiredstate]').set('value', '' + json.requiredstate);
    }

    // Disables/enables text input fields depending on checkbox.
    var updateCheckbox = function(check, focus) {
        var input = check.ancestor('label').next('label').one('input');
        var checked = check.get('checked');
        input.set('disabled', !checked);
        if (focus && checked) {
            input.focus();
        }
        return checked;
    };
    node.all('input[type=checkbox]').each(updateCheckbox);

    // Add event handlers (first time only).
    if (!M.availability_quizgradeitem.form.addedEvents) {
        M.availability_quizgradeitem.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_quizgradeitem select');
        root.delegate('change', function() {
            var ancestorNode = this.ancestor('span.availability_quizgradeitem');
            var quizNode = ancestorNode.one('select[name=quizid]');
            var questionNode = ancestorNode.one('select[name=questionbankentryid]');
            updateGradeItems(quizNode, questionNode);
        }, '.availability_quizgradeitem select[name=quizid]');
        root.delegate('click', function() {
            updateCheckbox(this, true);
            M.core_availability.form.update();
        }, '.availability_quizgradeitem input[type=checkbox]');
    }

    return node;
};

M.availability_quizgradeitem.form.fillValue = function(value, node) {
    var quizId = node.one('select[name=quizid]').get('value');
    var gradeItemId = node.one('select[name=quizid]').get('value');
    value.quizid = quizId === '' ? '' : parseInt(quizId, 10);
    value.gradeitemid = gradeItemId === '' ? '' : parseInt(gradeItemId, 10);
    value.min = node.one('select[name=quizid]').get('value');
    value.max = node.one('select[name=quizid]').get('value');
};

M.availability_quizgradeitem.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Quiz id must be set.
    if (value.quizid === '') {
        errors.push('availability_quizgradeitem:error_selectquiz');
    }
    // Grade item id must be set.
    if (value.questionbankentryid === '') {
        errors.push('availability_quizgradeitem:error_selectgradeitem');
    }
    // The max must be bigger than the min.
    if (value.min && value.max && value.max < value.min) {
        errors.push('availability_quizgradeitem:error_backwardrange');
    }
};
