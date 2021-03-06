/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'jquery',
    'Magento_Ui/js/grid/columns/column'
], function (_, $, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            previewImageSelector: '[data-image-preview]',
            visibile: null,
            height: 0,
            displayedRecord: {},
            lastOpenedImage: null,
            modules: {
                masonry: '${ $.parentName }',
                thumbnailComponent: '${ $.parentName }.thumbnail_url'
            },
            statefull: {
                visible: true,
                sorting: true,
                lastOpenedImage: true
            },
            listens: {
                '${ $.provider }:params.filters': 'hide',
                '${ $.provider }:params.search': 'hide'
            }
        },

        /**
         * Init observable variables
         * @return {Object}
         */
        initObservable: function () {
            this._super()
                .observe([
                    'visibile',
                    'height',
                    'displayedRecord',
                    'lastOpenedImage'
                ]);
            this.height.subscribe(function () {
                this.thumbnailComponent().previewHeight(this.height());
            }, this);

            return this;
        },

        /**
         * Next image preview
         *
         * @param {Object} record
         */
        next: function (record) {
            var recordToShow = this.getRecord(record._rowIndex + 1);

            recordToShow.rowNumber = record.lastInRow ? record.rowNumber + 1 : record.rowNumber;
            this.show(recordToShow);
        },

        /**
         * Previous image preview
         *
         * @param {Object} record
         */
        prev: function (record) {
            var recordToShow = this.getRecord(record._rowIndex - 1);

            recordToShow.rowNumber = record.firstInRow ? record.rowNumber - 1 : record.rowNumber;
            this.show(recordToShow);
        },

        /**
         * Get record
         *
         * @param {Integer} recordIndex
         *
         * @return {Object}
         */
        getRecord: function (recordIndex) {
            return this.masonry().rows()[recordIndex];
        },

        /**
         * Set selected row id
         *
         * @param {Number} rowId
         * @private
         */
        _selectRow: function (rowId) {
            this.thumbnailComponent().previewRowId(rowId);
        },

        /**
         * Show image preview
         *
         * @param {Object} record
         */
        show: function (record) {
            var img;

            this.hide();
            this.displayedRecord(record);
            this._selectRow(record.rowNumber || null);
            this.visibile(record._rowIndex);

            img = $(this.previewImageSelector + ' img');

            if (img.get(0).complete) {
                this.updateHeight();
                this.scrollToPreview();
            } else {
                img.load(function () {
                    this.updateHeight();
                    this.scrollToPreview();
                }.bind(this));
            }

            this.lastOpenedImage(record._rowIndex);
        },

        /**
         * Update image preview section height
         */
        updateHeight: function () {
            this.height($(this.previewImageSelector).height() + 'px');
        },

        /**
         * Close image preview
         */
        hide: function () {
            this.lastOpenedImage(null);
            this.visibile(null);
            this.height(0);
            this._selectRow(null);
        },

        /**
         * Returns visibility for given record.
         *
         * @param {Object} record
         * @return {*|bool}
         */
        isVisible: function (record) {
            if (this.lastOpenedImage() === record._rowIndex &&
                this.visibile() === null
            ) {
                this.show(record);
            }

            return this.visibile() === record._rowIndex || false;
        },

        /**
         * Get styles for preview
         *
         * @returns {Object}
         */
        getStyles: function () {
            return {
                'margin-top': '-' + this.height()
            };
        },

        /**
         * Scroll to preview window
         */
        scrollToPreview: function () {
            $(this.previewImageSelector).get(0).scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'nearest'
            });
        }
    });
});
