/*
	(c) 2015 Digital Humanities Lab, Utrecht University
	Author: Julian Gonggrijp
*/

'use strict';

var persistfork = {
	/**
	 * Options to be passed to vis.Network.
	 *
	 * @property object $nodes Default settings for rendering of nodes.
	 * @property object $edges Default settings for rendering of edges.
	 * @property object $layout Default settings for spatial layout of nodes.
	 */
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

	/**
	 * Hide the drawing arena and container.
	 *
	 * @see persistfork.visualise
	 */
	'close': function() {
		this.container.hide();
	},

	/**
	 * Draw a family graph, show the drawing arena and enable clickthrough.
	 *
	 * @see vis.Network
	 * @see vis.DataSet
	 * @link http://visjs.org/docs/network
	 * @global object $persistfork Contains the drawing arena and container,
	 *                             options and current network, if any.
	 * @listens persistfork.network:click
	 *
	 * @param object $data Family tree data generated in public_fork_box.php.
	 */
	visualise: function( data ) {
		var linkTable = {};
		if ( this.network ) {
			this.network.destroy();
		}
		this.network = new vis.Network( this.arena[0], {
			nodes: new vis.DataSet( data.nodes ),
			edges: new vis.DataSet( data.edges )
		}, this.options );
		this.container.show();
		for ( var i in data.nodes ) {
			linkTable[ '' + data.nodes[i].id ] = data.nodes[ i ].href;
		}
		this.network.on( 'click', function( params ) {
			if ( params.nodes && 1 === params.nodes.length ) {
				window.location = linkTable[ params.nodes[0] ];
			}
		});
	}
};

/**
 * Initialize the persistfork object and drawing arena on document ready.
 *
 * @see persistfork.visualise
 * @see persistfork.close
 * @global object $persistfork Stores the drawing arena and container.
 * @listens document:ready
 * @listens (#persistfork-container a):click
 */
jQuery( document ).ready(function( $ ) {
	var container = $( '<div>' )
		.attr( 'id', 'persistfork-container' )
	    .text( 'Family network' );
	var closeButton = $( '<a>' )
		.attr( 'href', '#' )
		.click(function( evt ) {
			evt.preventDefault();
			persistfork.close();
		})
		.text( 'Close' );
	var arena = $( '<div>' );
	container.append( closeButton, arena );
	$( document.body ).append( container );
	container.hide();

	persistfork.container = container;
	persistfork.arena = arena;
});
