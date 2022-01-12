<template>
  <div class="event-modal" @click.self="$emit('close')">
    <div class="modal-content card p-8" :class="{ 'overflow-hidden': showAttendees }">
      <button class="close" @click="$emit('close')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path class="heroicon-ui" d="M16.24 14.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 0 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12l2.83 2.83z"/></svg>
      </button>

      <form
        v-show="!showAttendees"
        action="#"
        method="post"
        @submit.prevent="onSubmit"
        class="p-8"
      >
        <h3 class="mb-6">{{ event ? `Edit Event: ${event.event.title}` : `Create Event: ${moment(date.date).format('MM/DD/YYYY')}` }}</h3>

        <label class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Title:</span>
          <input
            type="text"
            name="title"
            v-model="form.title"
            class="w-full form-control form-input form-input-bordered"
            required
          >
        </label>

        <label class="flex items-center py-2 w-max">
          <span class="mr-4 text-80 leading-tight">All Day:</span>
          <input type="checkbox" v-model="form.allDay" style="width: 18px; height: 18px;">
        </label>

        <div class="half-grid py-2">
          <label class="block" v-if="!form.allDay">
            <span class="block mb-2 text-80 leading-tight">Start:</span>
            <date-time-picker
              @change="changeStart"
              v-model="form.start"
              name="start"
              class="w-full form-control form-input form-input-bordered"
              autocomplete="off"
              :twelveHourTime="true"
            />
          </label>
          <label class="block" v-if="!form.allDay">
            <span class="block mb-2 text-80 leading-tight">End:</span>
            <date-time-picker
              @change="changeEnd"
              v-model="form.end"
              name="end"
              class="w-full form-control form-input form-input-bordered"
              autocomplete="off"
              :twelveHourTime="true"
            />
          </label>
        </div>

        <label class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Location:</span>
          <input
            type="text"
            name="location"
            v-model="form.location"
            class="w-full form-control form-input form-input-bordered"
          >
        </label>

        <label class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Description:</span>
          <textarea
            name="description"
            v-model="form.description"
            class="w-full form-input form-input-bordered p-2"
            rows="3"
          ></textarea>
        </label>

        <label v-if="!event" class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Repeats:</span>
          <select v-model="form.recurrence" class="w-1/2 form-control form-input form-input-bordered my-2">
            <option :value="null">Does not repeat</option>
            <option :value="['RRULE:FREQ=DAILY']">Every day</option>
            <option :value="['RRULE:FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR']">Every weekday (Monday to Friday)</option>
            <option :value="['RRULE:FREQ=WEEKLY;BYDAY=SA,SU']">Every weekend (Saturday and Sunday)</option>
            <option
              :value="[`RRULE:FREQ=WEEKLY;BYDAY=${moment(this.form.start).format('dd').toUpperCase()}`]"
            >
              Weekly on {{ moment(this.form.start).format('dddd') }}
            </option>
          </select>
        </label>

        <label class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Calendar:</span>
          <select v-model="form.calendar_id" class="w-1/2 form-control form-input form-input-bordered my-2" :disabled="event">
            <option
              v-for="calendar in calendars"
              v-if="event || calendar.summary !== 'Holidays in United States'"
              :value="calendar.id"
            >
              {{ calendar.primary ? 'Primary' : calendar.summary }}
            </option>
          </select>
        </label>

        <div class="flex items-end py-2">
          <label class="mr-4">
            <span class="block mb-2 text-80 leading-tight">Max Attendees:</span>
            <input
              type="number"
              name="max_attendees"
              v-model="form.max_attendees"
              min="1"
              class="form-control form-input form-input-bordered"
              style="width: 100px"
            >
          </label>

          <a
            v-if="event && !holidayEvent"
            href="#"
            @click.prevent="showAttendees = true"
            title="View/Add Attendees"
            class="font-bold"
            style="color: #1a252f"
          >
            View/Add Attendees
          </a>
        </div>

        <div v-if="!holidayEvent" class="pt-4 flex items-center">
          <button type="submit" class="btn btn-default btn-primary mr-4">
            <span v-if="!loading">{{ event ? 'Save Changes' : 'Create Event' }}</span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>

          <button v-if="event" class="btn btn-default btn-danger" @click.prevent="onDeleteEvent">
            <span v-if="!loadingDelete">Delete Event</span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>
        </div>
      </form>

      <event-attendees
        v-if="showAttendees"
        :event="event"
        @attendeeAdded="onAttendeeAdded($event)"
        @back="showAttendees = false"
      ></event-attendees>
    </div>

    <div v-if="showRecurrenceModal" class="event-modal" @click.self="showRecurrenceModal = false">
      <div class="recurrence-modal card p-8 relative">
        <button class="close" @click="showRecurrenceModal = false">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path class="heroicon-ui" d="M16.24 14.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 0 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12l2.83 2.83z"/></svg>
        </button>

        <h4 class="mb-4">Edit recurring event</h4>

        <label class="flex items-center py-2 w-max">
          <input type="radio" name="includeFollowing" :value="false" v-model="form.include_following" style="width: 18px; height: 18px;">
          <span class="ml-4 text-80 leading-tight">This event</span>
        </label>
        <label class="flex items-center py-2 w-max">
          <input type="radio" name="includeFollowing" :value="true" v-model="form.include_following" style="width: 18px; height: 18px;">
          <span class="ml-4 text-80 leading-tight">This and following events <sub class="text-danger block mt-1">* removes attendees from future events</sub></span>
        </label>

        <div class="pt-4 flex items-center">
          <button
            @click="() => { showRecurrenceModal === 'submit' ? submitForm() : deleteEvent() }"
            class="btn btn-default btn-primary mr-4"
          >
            <span>Ok</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import EventAttendees from './EventAttendees';

export default {
  name: 'EventModal',

  components: { EventAttendees },

  props: {
    event: { type: Object },
    date: { type: Object },
    calendars: { type: Array, required: true },
    user: { type: Object, required: true },
    user_is_admin: { type: Boolean, default: true },
    event_list_start: { type: String, default: null },
    event_list_end: { type: String, default: null },
  },

  data() {
    return {
      moment,
      form: {
        id: this.event ? this.event.event.id : null,
        calendar_id: null,
        title: this.event ? this.event.event.title : '',
        location: this.event ? this.event.event.extendedProps.location : '',
        description: this.event ? this.event.event.extendedProps.description : '',
        allDay: this.event ? this.event.event.allDay : false,
        start: moment(this.event ? this.event.event.start : this.date.date).format('YYYY-MM-DD HH:mm'),
        end: this.event
          ? moment(this.event.event.end).format('YYYY-MM-DD HH:mm')
          : moment(this.date.date).add(1, 'hour').format('YYYY-MM-DD HH:mm'),
        max_attendees: this.event && this.event.event.extendedProps.extendedProperties && this.event.event.extendedProps.extendedProperties.shared && this.event.event.extendedProps.extendedProperties.shared.max_attendees
          ? this.event.event.extendedProps.extendedProperties.shared.max_attendees
          : 200,
        recurrence: null,
        include_following: false,
        event_list_start: this.event_list_start,
        event_list_end: this.event_list_end
      },
      showAttendees: false,
      loading: false,
      loadingDelete: false,
      showRecurrenceModal: false,
    }
  },

  computed: {
    holidayEvent() {
      return (this.event)
        ? this.event.event.extendedProps.calendar_id === 'en.usa#holiday@group.v.calendar.google.com'
        : false;
    }
  },

  methods: {
    onSubmit() {
      if (this.event && this.event.event.extendedProps.recurringEventId && this.user_is_admin) {
        this.showRecurrenceModal = 'submit';
      } else {
        this.submitForm();
      }
    },
    submitForm() {
      this.showRecurrenceModal = false;
      this.loading = true;
      const endpoint = `/api/google-calendar/calendars/events/${this.event ? 'update' : 'create'}`;
      axios.post(endpoint, this.form)
        .then(r => {
          this.$emit('updateCalendars', { calendars: r.data.calendars, close: true });
          this.loading = false;
        })
        .catch(e => {
          console.log(e);
          this.loading = false;
        });
    },
    onDeleteEvent() {
      if (this.event && this.event.event.extendedProps.recurringEventId && this.user_is_admin) {
        this.showRecurrenceModal = 'delete';
      } else {
        this.deleteEvent();
      }
    },
    deleteEvent() {
      this.showRecurrenceModal = false;
      this.loadingDelete = true;
      axios.post('/api/google-calendar/calendars/events/delete', {
        id: this.form.id,
        calendar_id: this.form.calendar_id,
        include_following: this.form.include_following
      })
        .then(r => {
          this.$emit('updateCalendars', { calendars: r.data.calendars, close: true });
          this.loadingDelete = false;
        })
        .catch(e => {
          console.log(e);
          this.loadingDelete = false;
        });
    },
    changeStart(value) {
      this.form.start = value;
    },
    changeEnd(value) {
      this.form.end = value;
    },
    onAttendeeAdded(e) {
      this.$emit('updateCalendars', { calendars: e.data.calendars, close: false });
      this.event.event.extendedProps.attendees = e.data.event.attendees;
    },
    calIdInit() {
      if (this.event) {
        this.form.calendar_id =  this.event.event.extendedProps.calendar_id;
      } else if (!this.user_is_admin) {
        const calendar = this.calendars.find(cal => cal.summary === this.user.calendar);
        this.form.calendar_id = calendar ? calendar.id : null;
      } else {
        const primary = this.calendars.find(cal => cal.primary);
        this.form.calendar_id = primary ? primary.id : null;
      }
    }
  },

  created() {
    this.calIdInit();
    this.showAttendees = this.event && !this.user_is_admin && !this.holidayEvent;
  }
}
</script>

<style scoped>
.event-modal {
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

.modal-content {
  position: relative;
  width: 75%;
  min-width: 600px;
  max-width: 850px;
  min-height: 500px;
  max-height: 90%;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.5);
  overflow: auto;
}

.modal-content.overflow-hidden {
  overflow: hidden;
}

.close {
  position: absolute;
  top: 10px;
  right: 12px;
}

.close svg {
  width: 35px;
  height: 35px;
}

.half-grid {
  display: grid;
  grid-template-columns: repeat(2, 50%);
  grid-column-gap: 1rem;
}

.btn-primary {
  background-color: #1a252f;
}

.btn-secondary {
  color: #fff;
  background-color: rgba(26, 37, 47, .65);
}

.w-max {
  width: max-content;
}

.loader {
  width: 106px;
  height: 36px;
  object-fit: contain;
  object-position: center;
}

.recurrence-modal {
  width: 325px;
  height: auto;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.5);
}
</style>
