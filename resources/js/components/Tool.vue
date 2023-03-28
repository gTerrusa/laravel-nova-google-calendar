<template>
  <div class="p-8">
    <heading class="mb-6">Calendar</heading>

    <card class="p-8 calendar-layout">
      <div class="calendar-sidebar">
        <div v-if="rescheduling.email">
          <div class="sidebar-section">
            <h3 class="mb-2">Rescheduling</h3>
          </div>

          <div class="sidebar-section">
            <p>
              {{ rescheduling.email }}
            </p>
          </div>

          <div class="sidebar-section">
            <p>
              <b>From:</b> {{ formatDate(rescheduling.fromEvent.event._instance.range.start) }}
            </p>
          </div>

          <div class="sidebar-section">
            <button
              class="btn btn-default btn-danger"
              @click="cancelRescheduling"
            >
              <span>Cancel</span>
            </button>

          </div>
        </div>
        <div v-else>
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
              <span class="flex items-center mr-4 text-80 leading-tight cursor-pointer" @click.prevent="editCalendar(calendar)">
                <span class="bul mr-2" :style="{ backgroundColor: calendar.backgroundColor }"></span>  {{ calendar.summary }}
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
              @click="calModal = true"
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
      :user_is_admin="user_is_admin"
      :event_list_start="event_list_start"
      :event_list_end="event_list_end"
      @close="closeModal"
      @updateCalendars="updateCalendars($event)"
    ></event-modal>

    <cal-modal
      v-if="calModal"
      :calendar="calToEdit"
      @close="calModal = false; calToEdit = null"
      @updateCalendars="updateCalendars($event)"
    ></cal-modal>

    <attendee-download-modal
      v-if="downloadModal"
      :calendars="calendars"
      @close="downloadModal = false"
    ></attendee-download-modal>

    <div
      v-if="rescheduling && rescheduling.fromEvent && rescheduling.toEvent"
      class="rescheduling-modal"
    >
      <div class="card p-8">
        <h3 class="mb-2">
          Confirm Reschedule
        </h3>

        <p class="mt-2 mb-2">
          <b>From:</b> {{ formatDate(rescheduling.fromEvent.event._instance.range.start) }}
        </p>

        <p class="mb-2">
          <b>To:</b> {{ formatDate(rescheduling.toEvent.event._instance.range.start) }}
        </p>

        <div class="pt-2">
          <button
            class="btn btn-default btn-primary mr-2"
            @click="reschedule"
          >
            <span v-if="!isRescheduling">Confirm</span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>

          <button
            @click="rescheduling.toEvent = null"
            class="btn btn-default btn-danger"
          >
            <span>Cancel</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import FullCalendar from '@fullcalendar/vue';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import EventModal from './EventModal';
import CalModal from './CalModal';
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
    CalModal,
    AttendeeDownloadModal
  },

  data() {
    return {
      calendars: [],
      selectedCalendars: [],
      currentEvent: null,
      currentDate: null,
      showModal: false,
      calModal: false,
      calToEdit: null,
      downloadModal: false,
      filter: !Nova.config.user_is_admin,
      loading: false,
      refreshCount: 0,
      user: Nova.config.user,
      user_is_admin: Nova.config.user_is_admin,
      event_list_start: null,
      event_list_end: null,
      eventListUpdated: 0,
      rescheduling: {
        email: '',
        fromEvent: null,
        toEvent: null
      },
      isRescheduling: false
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
        datesSet: this.handleDatesSet,
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
              if (this.rescheduling.email) {
                return !this.isEventFull(e)
              }

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

  watch: {
    eventListUpdated (val) {
      this.getCalendars(this.event_list_start, this.event_list_end)
    },
  },

  methods: {
    getCalendars (eventsStart = null, eventsEnd = null) {
      axios.get(`/api/google-calendar/calendars?event_list_start=${encodeURIComponent(eventsStart)}&event_list_end=${encodeURIComponent(eventsEnd)}`)
        .then(r => {
          this.calendars = this.user_is_admin
            ? r.data.filter(cal => !cal.primary)
            : r.data.filter(cal => cal.summary === this.user.calendar);
          this.selectedCalendars = r.data.map(cal => cal.id);
        })
        .catch(e => console.log(e));
    },
    handleDatesSet(dateInfo) {
      if (this.event_list_start !== dateInfo.start.toLocaleDateString('en-US') || this.event_list_end !== dateInfo.end.toLocaleDateString('en-US')) {
        this.event_list_start = dateInfo.start.toLocaleDateString('en-US')
        this.event_list_end = dateInfo.end.toLocaleDateString('en-US')
        this.eventListUpdated++
      }

    },
    handleDateClick(date) {
      this.showModal = true;
      this.currentDate = date;
    },
    handleEventClick(event) {
      // TODO: open modal to confirm reschedule if rescheduling
      if (this.rescheduling.email) {
        this.rescheduling.toEvent = _.cloneDeep(event)
        console.log(this.rescheduling.toEvent.event._instance.range.start)
        return
      }

      this.showModal = true;
      this.currentEvent = event;
    },
    closeModal() {
      this.showModal = false;
      this.currentEvent = null;
      this.currentDate = null;
    },
    editCalendar (calendar) {
      this.calToEdit = calendar
      this.calModal = true
    },
    updateCalendars(e) {
      this.calendars = this.user_is_admin
        ? e.calendars.filter(cal => !cal.primary)
        : e.calendars.filter(cal => cal.summary === this.user.calendar);
      this.refreshCount++;
      if (e.close) {
        this.closeModal();
        this.calModal = false;
        this.calToEdit = null;
      }
    },
    async reschedule () {
      this.isRescheduling = true
      const attendee = _.cloneDeep(this.rescheduling)

      delete attendee.fromEvent
      delete attendee.toEvent

      try {
        await axios.post('/api/google-calendar/calendars/events/attendees/remove', {
          id: this.rescheduling.fromEvent.event.id,
          attendee,
          calendar_id: this.rescheduling.fromEvent.event.extendedProps.calendar_id
        })

        var { data } = await axios.post('/api/google-calendar/calendars/events/attendees/add', {
          id: this.rescheduling.toEvent.event.id,
          attendee,
          calendar_id: this.rescheduling.toEvent.event.extendedProps.calendar_id
        })
      } catch (err) {
        if (err && err.response && err.response.data) {
          Nova.error(err.response.data)
        } else {
          Nova.error('Error rescheduling attendee in google.')
        }
        this.cancelRescheduling()
        return
      }

      if (Nova.config.save_attendees_to_db) {
        try {
          await axios.post(Nova.config.attendee_create_or_update_path, {
            email: attendee.email,
            name: attendee.displayName,
            calendar_id: this.rescheduling.fromEvent.event.extendedProps.calendar_id,
            event_id_remove: this.rescheduling.fromEvent.event.id,
            event_id: this.rescheduling.toEvent.event.id,
            appointment: this.formatAppointment(attendee, this.rescheduling.toEvent.event)
          })
        } catch (err) {
          if (err && err.response && err.response.data) {
            Nova.error(err.response.data)
          } else {
            Nova.error('Error rescheduling attendee in db.')
          }
          this.cancelRescheduling()
          return
        }
      }

      Nova.success('Attendee Rescheduled!')
      this.updateCalendars(data)
      this.cancelRescheduling()
    },
    onReschedule (attendee) {
      for (const prop in attendee) {
        this.rescheduling[prop] = attendee[prop]
      }
      this.rescheduling.fromEvent = _.cloneDeep(this.currentEvent)
      this.closeModal()
    },
    cancelRescheduling () {
      this.isRescheduling = false
      this.rescheduling = { email: '', fromEvent: null, toEvent: null }
    },
    isEventFull (event) {
      const attendees = event.attendees || []
      const maxAttendees = event.extendedProperties && event.extendedProperties.shared
        ? event.extendedProperties.shared.max_attendees || 99999
        : 99999;
      return attendees.filter(a => a.responseStatus !== 'declined').length >= parseInt(maxAttendees);
    },
    formatDate (date) {
      return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      })
    },
    formatAppointment (attendee, event) {
      const calendar = this.calendars.find(cal => cal.id === event.extendedProps.calendar_id)
      const additionalData = this.additionalData(calendar)

      let appointment = {
        calendar_name: calendar.summary,
        event_name: event.extendedProps.summary,
        event_start: event.extendedProps.googleStart.date || event.extendedProps.googleStart.dateTime,
        response_status: attendee.responseStatus,
        no_show: !!(attendee.comment && attendee.comment === 'No Show')
      }

      if (additionalData && additionalData.length) {
        let additional_data = {}

        additionalData.forEach((item) => {
          if (typeof attendee[item.field] !== 'undefined') {
            additional_data[item.field] = attendee[item.field]
          }
        })

        appointment.additional_data = additional_data
      }

      return appointment
    },
    additionalData (calendar) {
      return (Nova.config.db_attendee_additional_info && Array.isArray(Nova.config.db_attendee_additional_info))
        ? Nova.config.db_attendee_additional_info.filter(item => item.calendars && Array.isArray(item.calendars) && item.calendars.includes(calendar.summary)) || []
        : []
    },
  },

  created () {
    this.$root.$on('reschedule', this.onReschedule)
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

.rescheduling-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(255, 255, 255, 0.3);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rescheduling-modal > div {
  position: relative;
  max-width: max(75%, 850px);
  max-height: 90%;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.5);
  overflow: auto;
}
</style>
