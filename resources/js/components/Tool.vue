<template>
    <div class="p-8">
        <heading class="mb-6">Calendar</heading>

        <card class="p-8 calendar-layout">
            <div class="calendar-sidebar">
                <div class="sidebar-section">
                    <h3 class="mb-2">Filter Results</h3>

                    <select v-model="filter" class="w-full form-control form-input form-input-bordered my-2">
                        <option :value="false">All Events</option>
                        <option :value="true">Events With Attendees</option>
                    </select>
                </div>

                <div class="sidebar-section">
                    <h3 class="mb-2">Calendars</h3>

                    <label v-for="calendar in calendars" class="flex items-center justify-between py-2 w-max">
                        <span class="flex items-center mr-4 text-80 leading-tight">
                            <span class="bul mr-2" :style="{ backgroundColor: calendar.backgroundColor }"></span>  {{ calendar.primary ? 'Primary' : calendar.summary }}
                        </span>
                        <input
                            type="checkbox"
                            :value="calendar.id"
                            v-model="selectedCalendars"
                            style="width: 18px; height: 18px;"
                        >
                    </label>

                    <button
                        v-if="user_is_admin"
                        @click="newCalModal = true"
                        class="btn btn-default btn-outline rounded-lg mt-4"
                    >
                        + New Calendar
                    </button>
                </div>

                <div class="sidebar-section">
                    <button @click="downloadModal = true" class="btn btn-default btn-primary">
                        Download Attendees
                    </button>
                </div>

                <div class="sidebar-section">
                    <button @click="clearCalendarCache" class="btn btn-default btn-primary">
                        <span v-if="!loading">Refresh Calendar</span>
                        <img v-else src="../../img/loading/Spinner.gif" alt="loading" class="loader">
                    </button>
                </div>
            </div>

            <full-calendar
                v-if="events"
                ref="fullCalendar"
                :options="calendarOptions"
                :key="`full-calendar-${selectedCalendars.length}-${filter ? 'filtered' : 'unfiltered'}-${refreshCount}`"
            ></full-calendar>
        </card>

        <event-modal
            v-if="showModal"
            :event="currentEvent"
            :calendars="calendars"
            :date="currentDate"
            :user="user"
            @close="closeModal"
            @updateCalendars="updateCalendars($event)"
        ></event-modal>

        <new-cal-modal
            v-if="newCalModal"
            @close="newCalModal = false"
            @updateCalendars="updateCalendars($event)"
        ></new-cal-modal>

        <attendee-download-modal
            v-if="downloadModal"
            :calendars="calendars"
            @close="downloadModal = false"
        ></attendee-download-modal>
    </div>
</template>

<script>
import FullCalendar from '@fullcalendar/vue';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import EventModal from './EventModal';
import NewCalModal from './NewCalModal';
import AttendeeDownloadModal from './AttendeeDownloadModal';

export default {
    metaInfo() {
        return {
            title: 'LaravelNovaGoogleCalendar',
        }
    },

    components: {
      FullCalendar,
      EventModal,
      NewCalModal,
      AttendeeDownloadModal
    },

    data() {
        return {
            calendars: [],
            selectedCalendars: [],
            currentEvent: null,
            currentDate: null,
            showModal: false,
            newCalModal: false,
            downloadModal: false,
            filter: !Nova.config.user_is_admin,
            loading: false,
            refreshCount: 0,
            user: Nova.config.user,
            user_is_admin: Nova.config.user_is_admin
        }
    },

    computed: {
        calendarOptions() {
            return {
                plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin ],
                editable: true,
                selectable: true,
                events: this.events,
                initialView: 'dayGridMonth',
                locale: 'en',
                dateClick: this.handleDateClick,
                eventClick: this.handleEventClick,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                },
                dayMaxEvents: true,
            }
        },

        events() {
            return this.calendars
                .filter(cal => this.selectedCalendars.includes(cal.id))
                .flatMap(cal => {
                    return cal.events
                        .filter(e => {
                            return this.filter
                                ? e.attendees && e.attendees.length
                                : true;
                        })
                        .map(e => {
                            e.title = e.summary;
                            e.attendees = e.attendees || [];
                            e.backgroundColor = cal.backgroundColor;
                            e.borderColor = cal.backgroundColor;
                            e.googleStart = e.googleStart || e.start;
                            e.googleEnd = e.googleEnd || e.end;
                            e.start = e.googleStart ? e.googleStart.date || e.googleStart.dateTime : null;
                            e.end = e.googleEnd ? e.googleEnd.date || e.googleEnd.dateTime : null;
                            return e;
                        }) || []
                });
        }
    },

    methods: {
        handleDateClick(date) {
            console.log(date.date);
            this.showModal = true;
            this.currentDate = date;
        },
        handleEventClick(event) {
            console.log(event.event);
            this.showModal = true;
            this.currentEvent = event;
        },
        closeModal() {
            this.showModal = false;
            this.currentEvent = null;
            this.currentDate = null;
        },
        updateCalendars(e) {
            this.calendars = this.user_is_admin
                ? e.calendars
                : e.calendars.filter(cal => cal.summary === this.user.calendar || cal.summary === 'Holidays in United States');
            this.refreshCount++;
            if (e.close) {
                this.closeModal();
                this.newCalModal = false;
            }
        },
        clearCalendarCache() {
            this.loading = true;
            axios.get('/api/google-calendar/calendars/refresh-cache')
                .then(r => {
                    this.calendars = this.user_is_admin
                        ? r.data
                        : r.data.filter(cal => cal.summary === this.user.calendar || cal.summary === 'Holidays in United States');
                    this.refreshCount++;
                    this.loading = false;
                })
                .catch(e => {
                    console.log(e);
                    this.loading = false;
                });
        },
    },

  created() {
    axios.get('/api/google-calendar/calendars')
      .then(r => {
        this.calendars = this.user.is_admin
          ? r.data
          : r.data.filter(cal => cal.summary === this.user.calendar || cal.summary === 'Holidays in United States');
        this.selectedCalendars = r.data.map(cal => cal.id);
      })
      .catch(e => console.log(e));
  }
}
</script>

<style scoped>
.calendar-layout {
    display: grid;
    grid-template-columns: max-content 1fr;
    grid-column-gap: 1rem;
}

.calendar-sidebar {
    padding-right: .5rem;
    border-right: solid 1px rgba(26, 37, 47, .65);
}

.sidebar-section {
    padding-top: 1rem;
    padding-bottom: 1rem;
    border-bottom: solid 1px rgba(26, 37, 47, .65);
}

.btn-primary {
    background-color: #1a252f;
}

.loader {
    width: 131px;
    height: 36px;
    object-fit: contain;
    object-position: center;
}

.bul {
    width: 10px;
    height: 10px;
    border-radius: 100%;
    display: inline-block;
}
</style>
