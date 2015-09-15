// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Atto text editor integration version file.
 *
 * @package    atto_oembed
 * @copyright  Erich M. Wappis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_oembed-button
 */

/**
 * Atto text editor oembed plugin.
 *
 * @namespace M.atto_oembed
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENTNAME = 'atto_oembed';
var MEDIAURL = 'oembed_url';
var LOGNAME = 'atto_oembed';

var CSS = {
        INPUTSUBMIT: 'atto_media_urlentrysubmit',
        INPUTCANCEL: 'atto_media_urlentrycancel',
        MEDIAURL: 'MEDIAURL'
    },
    SELECTORS = {
        MEDIAURL: '.MEDIAURL'
    };

var TEMPLATE = '' +
    '<form class="atto_form">' +
        '<div id="{{elementid}}_{{innerform}}" class="mdl-align">' +
            '<label for="{{elementid}}_{{MEDIAURL}}">{{get_string "enterurl" component}}</label>' +
            '<input class="{{CSS.MEDIAURL}} id="{{elementid}}_{{MEDIAURL}}" name="{{elementid}}_{{MEDIAURL}}" value="{}" />' +
            '<button class="{{CSS.INPUTSUBMIT}}">{{get_string "insert" component}}</button>' +
        '</div>' +
        'icon: {{clickedicon}}'  +
    '</form>';


Y.namespace('M.atto_oembed').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

  
	/**
     * Initialize the button
     *
     * @method Initializer
     */
    initializer: function() {
        // If we don't have the capability to view then give up.
        if (this.get('disabled')){
            return;
        }

        var twoicons = ['insert_embed_media'];

        Y.Array.each(twoicons, function(theicon) {
            // Add the oembed icon/buttons
            this.addButton({
                icon: 'ed/' + theicon,
                iconComponent: 'atto_oembed',
                buttonName: theicon,
                callback: this._displayDialogue,
                callbackArgs: theicon
            });
        }, this);

    },

    /**
     *
     * @method _getMEDIAURLName
     * @return {String} the name/id of the mediaurl form field
     * @private
     */
    _getMEDIAURLName: function(){
        return(this.get('host').get('elementid') + '_' + MEDIAURL);
    },

     /**
     * Display the oembed Dialogue
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function(e, clickedicon) {
        e.preventDefault();
        var width=400;


        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('dialogtitle', COMPONENTNAME),
            width: width + 'px',
            focusAfterHide: clickedicon
        });
		//dialog doesn't detect changes in width without this
		//if you reuse the dialog, this seems necessary
        if(dialogue.width !== width + 'px'){
            dialogue.set('width',width+'px');
        }

        //append buttons to iframe
        var buttonform = this._getFormContent(clickedicon);

        var bodycontent =  Y.Node.create('<div></div>');
        bodycontent.append(buttonform);

        //set to bodycontent
        dialogue.set('bodyContent', bodycontent);
        dialogue.show();
        this.markUpdated();
    },


     /**
     * Return the dialogue content for the tool, attaching any required
     * events.
     *
     * @method _getDialogueContent
     * @return {Node} The content to place in the dialogue.
     * @private
     */
    _getFormContent: function(clickedicon) {
        var template = Y.Handlebars.compile(TEMPLATE),
            content = Y.Node.create(template({
                elementid: this.get('host').get('elementid'),
                CSS: CSS,
                MEDIAURL: MEDIAURL,
                component: COMPONENTNAME,
                defaultmediatext: this.get('defaulttext'),
                clickedicon: clickedicon
            }));

        this._form = content;
        this._form.one('.' + CSS.INPUTSUBMIT).on('click', this._doInsert, this);

        return content;
    },

    

    
    /**
     * Inserts the users input onto the page
     * @method _getDialogueContent
     * @private
     */
    _doInsert : function(e){
        e.preventDefault();
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        var MEDIAURL = this._form.one(SELECTORS.MEDIAURL);

        var url = M.cfg.wwwroot + '/lib/editor/atto/plugins/oembed/ajax2.php';
        var params = {
            sesskey: M.cfg.sesskey,
            action: 'filtertext',
            text: MEDIAURL.get('value')
        };

        var self = this;

        var process_resp = function (respobj) {
        
            if (!respobj.success) {
                // TODO - nice localised error message required.
                alert ('Failed to do oembed');
            }

            self.editor.focus();
            
            self.get('host').insertContentAtFocusPoint(respobj.htmloutput);
            self.markUpdated();
        };

        var preview = Y.io(url, {
            sync: true,
            data: params,
            method: 'POST',
            on : {
                success: function (tx, r) {
                    var respobj = {};
                    try {
                        respobj = Y.JSON.parse(r.responseText);
                    }
                    catch (e) {
                        //TODO - do something nice with this.
                        alert("JSON Parse failed!");
                        return;
                    }
                    process_resp(respobj);
                }
            }
        });

    }
}, { ATTRS: {
		disabled: {
			value: false
		},

		usercontextid: {
			value: null
		},

		defaultflavor: {
			value: ''
		}
	}
});
