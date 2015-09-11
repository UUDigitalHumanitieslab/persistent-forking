/*
    (c) 2015 Digital Humanities Lab, Utrecht University
    Author: Julian Gonggrijp
*/

'use strict';

var persistfork = {
    options: {
        nodes: {
            shadow: true,
            shape: 'box'
        },
        edges: {
            shadow: true,
            arrows: 'to'
        },
        layout: {
            randomSeed: 1,
            hierarchical: {
                direction: 'UD'
            }
        }
    },

    'close': function() {
        this.container.hide();
    },

    visualise: function(data) {
        if (this.network) this.network.destroy();
        this.network = new vis.Network(this.arena[0], {
            nodes: new vis.DataSet(data.nodes),
            edges: new vis.DataSet(data.edges)
        }, this.options);
        this.container.show();
    }
};

jQuery(document).ready(function($) {
    var container = $('<div>').attr('id', 'persistfork-container').text('Family network');
    var closeButton = $('<a>').attr('href', '#')
                              .click(function(evt) {
                                  evt.preventDefault();
                                  persistfork.close();
                              })
                              .text('Close');
    var arena = $('<div>');
    container.append(closeButton, arena);
    $(document.body).append(container);
    container.hide();
    
    persistfork.container = container;
    persistfork.arena = arena;
    
});