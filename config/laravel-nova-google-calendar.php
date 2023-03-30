<?php

return [
    /**
     * The accessor name of the attribute on your User model
     * to use to check if a User is an Admin.
     * Set to null to give all User's Admin privileges.
     */
    'user_admin_boolean' => null,

    /**
     * Should attendees be saved to Laravel Database?
     */
    'save_attendees_to_db' => false,

    /**
     * If saving attendees to Laravel Database, what path should be used to save them?
     */
    'attendee_create_or_update_path' => '/api/leads/createOrUpdate',

    /*
     * Fields to hide in the "Attendee List" form.
     *
     * example: 'attendee_hidden_info' => ['name', 'status'],
     *
     */
    'attendee_hidden_info' => [],

    /**
     * Additional data to send to the Laravel database.
     *
     * example:
     * 'db_attendee_additional_info' => [
     *     [
     *         'field' => 'picked_up',
     *         'label' => 'Picked Up',
     *         'input' => 'checkbox',
     *         'type' => 'boolean',
     *         'default' => false,
     *         'calendars' => [
     *             'Gift Card Pick-Up'
     *         ]
     *     ],
     *     [
     *         'field' => 'gift_card_no',
     *         'label' => 'Gift Card Number',
     *         'input' => 'text',
     *         'type' => 'string',
     *         'default' => '',
     *         'calendars' => [
     *             'Gift Card Pick-Up'
     *         ]
     *     ]
     * ],
     */
    'db_attendee_additional_info' => [],

    /**
     * The endpoint to fetch the additional data from the Laravel database.
     * Should be a 'POST' endpoint, and accept an array called 'attendees'
     * and return the same array with the additional data appended to each attendee.
     */
    'fetch_db_attendee_additional_info_path' => null,

    /**
     * An array of Calendar's that don't allow attendees to be added via the Calendar tool.
     *
     * example:
     * 'add_attendees_disabled' => ['Gift Card Pick-Up'],
     */
    'add_attendees_disabled' => [],

    /**
     * Send Google Calendar Event Summary by default?
     */
    'default_event_summary' => false,

    'default'
];
