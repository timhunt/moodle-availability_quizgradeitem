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
 * States available for selection.
 *
 * @property states
 * @type Array
 */
// M.availability_quizgradeitem.form.states = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} quizzes Array of objects containing quiz .id and .name
 * @param {Array} states Array of objects containing state .shortname and .displayname
 */
M.availability_quizgradeitem.form.initInner = function(quizzes) {
    this.quizzes = quizzes;
    window.console.log(quizzes);
    // this.states = states;
};

M.availability_quizgradeitem.form.getNode = function(json) {
    // Init variables
    var i = 0;
    var html = '<br>';

    // Get strings
    var title = M.util.get_string('title', 'availability_quizgradeitem');
    var label_gradeitem = M.util.get_string('label_gradeitem', 'availability_quizgradeitem');
    var choosedots = M.util.get_string('choosedots', 'moodle');
    var option_min = M.util.get_string('option_min', 'availability_quizgradeitem');
    var label_min = M.util.get_string('label_min', 'availability_quizgradeitem');
    var option_max = M.util.get_string('option_max', 'availability_quizgradeitem');
    var label_max = M.util.get_string('label_max', 'availability_quizgradeitem');

    // Quizzes
    html += '<label class="mb-3"><span class="pe-3">' + title + '</span> ' +
                '<span class="availability-group">' +
                '<select name="quizid" class="custom-select">' +
                    '<option value="0">' + choosedots + '</option>';
    for (i = 0; i < this.quizzes.length; i++) {
        // String has already been escaped using format_string.
        html += '<option value="' + this.quizzes[i].id + '">' + this.quizzes[i].name + '</option>';
    }
    html += '</select></span></label><br>';

    // Grading categories
    html += '<label class="mb-3"><span class="pe-3">' + label_gradeitem + '</span> ' +
                '<span class="availability-group">' +
                '<select name="quizid" class="custom-select">' +
                    '<option value="0">' + choosedots + '</option>';
    for (i = 0; i < this.quizzes.length; i++) {
        // String has already been escaped using format_string.
        html += '<option value="' + this.quizzes[i].id + '">' + this.quizzes[i].name + '</option>';
    }
    html += '</select></span></label><br>';

    // Min
    html += '<span class="availability-group mb-3">' +
                '<label><input type="checkbox" class="form-check-input position-static mt-0 mx-1" name="max"/>' +
                    option_min +
                '</label>' +
                '<label>' +
                    '<span class="accesshide">' + label_min + '</span>' +
                    '<input type="text" class="form-control mx-1" name="minval" title="' + label_min + '"/>' +
                '</label>' +
            ' %</span>' +
            '<br>';

    // Max
    html += '<span class="availability-group mb-3">' +
                '<label><input type="checkbox" class="form-check-input position-static mt-0 mx-1" name="max"/>' +
                    option_max +
                '</label>' +
                '<label>' +
                    '<span class="accesshide">' + label_max + '</span>' +
                    '<input type="text" class="form-control mx-1" name="minval" title="' + label_max + '"/>' +
                '</label>' +
            ' %</span>'+
            '<br>';

    var node = Y.Node.create('<div class="d-inline-block d-flex flex-wrap align-items-center">' + html + '</div>');

    var updateQuestions = function(quizNode, questionNode, callback) {
        var quizId = quizNode.get('value');
        var url = M.cfg.wwwroot + '/availability/condition/quizgradeitem/ajax.php?quizid=' + quizId;
        // First, remove all options except the first one from the question drop-down menu.
        questionNode.all('option').each(function(optionNode) {
            if (optionNode.get('value') !== '') {
                optionNode.remove();
            }
        }, this);

        if (quizId) {
            // Disable the quiz element until we finish loading it's questions.
            quizNode.set('disabled', true);
            var pendingKey = {};
            M.util.js_pending(pendingKey);
            Y.io(url, {
                on: {
                    success: function(id, response) {
                        var questions = Y.JSON.parse(response.responseText);
                        for (var i = 0; i < questions.length; i++) {
                            var questionOption = document.createElement('option');
                            questionOption.value = questions[i].id;
                            questionOption.innerHTML = questions[i].name;
                            questionNode.append(questionOption);
                        }
                        // Questions are loaded, so we enable the quiz element now.
                        quizNode.set('disabled', false);

                        if (callback !== undefined) {
                            callback();
                        }

                        M.core_availability.form.update();
                        M.util.js_complete(pendingKey);
                    },
                    failure: function(id, response) {
                        // Loading failed. Let's enable the quiz so the user can try again.
                        quizNode.set('disabled', false);
                        M.util.js_complete(pendingKey);

                        var debugInfo = response.statusText;
                        if (M.cfg.developerdebug) {
                            debugInfo += ' (' + url + ')';
                        }
                        new M.core.exception({message: debugInfo});
                    }
                }
            });
        }
    };

    // Set initial value if specified.
    if (json.quizid !== undefined &&
            node.one('select[name=quizid] > option[value=' + json.quizid + ']')) {
        node.one('select[name=quizid]').set('value', '' + json.quizid);
        updateQuestions(node.one('select[name=quizid]'), node.one('select[name=questionbankentryid]'), function() {
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
            updateQuestions(quizNode, questionNode);
        }, '.availability_quizgradeitem select[name=quizid]');
        root.delegate('click', function() {
            updateCheckbox(this, true);
            M.core_availability.form.update();
        }, '.availability_quizgradeitem input[type=checkbox]');
    }

    return node;
};

M.availability_quizgradeitem.form.fillValue = function(value, node) {
    var quizid = node.one('select[name=quizid]').get('value');
    // var questionbankentryid = node.one('select[name=questionbankentryid]').get('value');
    // var state = node.one('select[name=requiredstate]').get('value');

    value.quizid = quizid === '' ? '' : parseInt(quizid, 10);
    // value.questionbankentryid = questionbankentryid === '' ? '' : parseInt(questionbankentryid, 10);
    // value.requiredstate = state;
};

M.availability_quizgradeitem.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    if (value.quizid === '') {
        errors.push('availability_quizgradeitem:error_selectquiz');
    }
    if (value.questionbankentryid === '') {
        errors.push('availability_quizgradeitem:error_selectquestion');
    }
    if (value.requiredstate === '') {
        errors.push('availability_quizgradeitem:error_selectstate');
    }
};
