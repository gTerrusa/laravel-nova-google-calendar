<template>
  <div class="event-attendees p-8">
    <h3 class="flex items-center justify-between mb-6">
      <span>Manage Attendees</span>

      <button class="flex items-center w-max" @click="$emit('back')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>

        <span class="ml-2 text-base">View Event</span>
      </button>
    </h3>

    <div class="attendee-grid py-2">
      <form action="#" method="post" @submit.prevent="addAttendee" class="pb-4 border-b border-gray">
        <h4 class="mb-2">Add Attendee</h4>

        <div class="form-grid items-end">
          <label class="block">
            <span class="block mb-2 text-80 leading-tight">Name:</span>
            <input
              type="text"
              name="name"
              v-model="form.attendee.displayName"
              class="w-full form-control form-input form-input-bordered"
              placeholder="Full Name"
              required
            >
          </label>

          <label class="block">
            <span class="block mb-2 text-80 leading-tight">Email:</span>
            <input
              type="email"
              name="email"
              v-model="form.attendee.email"
              class="w-full form-control form-input form-input-bordered"
              placeholder="Email Address"
              required
            >
          </label>

          <button type="submit" class="btn btn-default btn-primary" :disabled="eventFull">
            <span v-if="!loading">{{ eventFull ? 'Event Full' : 'Invite' }}</span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>
        </div>
      </form>

      <div class="overflow-hidden scroll-grid">
        <h4 class="mb-2">Attendee List</h4>

        <div class="overflow-auto">
          <p class="py-1" v-if="!attendees.length">There are no attendees for this event..</p>

          <div v-if="attendees.length" class="form-grid items-center py-1">
            <h4>Name</h4>
            <h4>Email</h4>
            <h4>Status</h4>
          </div>

          <div class="form-grid items-center py-1" v-for="attendee in attendees">
            <p>{{ attendee.displayName || attendee.email.split('@')[0] }}</p>

            <p>{{ attendee.email }}</p>

            <select v-model="attendee.responseStatus" @change="updateStatus(attendee)" class="form-control form-input form-input-bordered my-2">
              <option value="needsAction">Needs Action</option>
              <option value="accepted">Accepted</option>
              <option value="declined">Declined</option>
              <option value="tentative">Tentative</option>
            </select>
          </div>
        </div>
      </div>

      <div class="pt-4">
        <button class="btn btn-default btn-primary" @click="downloadAttendees">
          <span v-if="!attendeeLoading">Download</span>
          <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
        </button>
      </div>
    </div>
  </div>
</template>

<script>
const fileDownload = require('js-file-download');

export default {
  name: 'EventAttendees',

  props: {
    event: { type: Object, required: true }
  },

  data() {
    return {
      attendees: this.event.event.extendedProps.attendees || [],
      form: {
        id: this.event.event.id,
        calendar_id: this.event.event.extendedProps.calendar_id || null,
        attendee: {
          displayName: '',
          email: '',
          responseStatus: 'needsAction'
        }
      },
      loading: false,
      attendeeLoading: false,
    }
  },

  computed: {
    eventFull() {
      const maxAttendees = this.event.event.extendedProps.extendedProperties
        ? this.event.event.extendedProps.extendedProperties.shared.max_attendees
        : 99999;
      return this.attendees.filter(a => a.responseStatus !== 'declined').length >= parseInt(maxAttendees);
    },
    fileName() {
      return this.form.calendar_id + '_' + this.event.event.title + '_' + (new Date(this.event.event.start).toLocaleString()).replaceAll(' ', '_').replaceAll(',', '') + '.csv';
    }
  },

  methods: {
    async saveAttendeeToDB() {
      if (Nova.config.save_attendees_to_db) {
        return await axios.post(Nova.config.attendee_create_or_update_path, {
          email: this.form.attendee.email,
          name: this.form.attendee.displayName
        })
      }

      return true
    },
    async addAttendeeToCalendar() {
      return await axios.post('/api/google-calendar/calendars/events/attendees/add', this.form)
    },
    async addAttendee() {
      this.loading = true;

      try {
        const { data } = await this.addAttendeeToCalendar()
      } catch (err) {
        window.alert(err.response.data.message)
        this.loading = false
        return
      }

      try {
        await this.saveAttendeeToDB()
      } catch (err) {
        window.alert(err)
        this.loading = false
        return
      }

      const attendee = _.cloneDeep(this.form.attendee)

      this.$emit('attendeeAdded', { attendee, data });
      this.attendees.push(attendee);
      this.form.attendee.email = '';
      this.form.attendee.displayName = '';
      this.loading = false;
    },
    updateStatus(attendee) {
      axios.post('/api/google-calendar/calendars/events/attendees/update', {
        id: this.event.event.id,
        calendar_id: this.event.event.extendedProps.calendar_id || null,
        attendee: attendee
      })
        .then(r => {
          this.$emit('attendeeAdded', { attendee, data: r.data });
        })
        .catch(e => console.log(e));
    },
    downloadAttendees() {
      this.attendeeLoading = true;
      axios.post('/api/google-calendar/calendars/events/attendees/download', {
        calendar_id: this.event.event.extendedProps.calendar_id,
        event_id: this.event.event.id
      })
        .then(r => {
          fileDownload(r.data, this.fileName);
          this.attendeeLoading = false;
        })
        .catch(e => {
          console.log(e);
          this.attendeeLoading = false;
        });
    },
  }
}
</script>

<style scoped>
.attendee-grid {
  display: grid;
  grid-template-rows: max-content 300px;
  gap: 1rem;
  overflow: hidden;
}

.btn-primary {
  background-color: #1a252f;
}

.w-max {
  width: max-content;
}

.scroll-grid {
  display: grid;
  grid-template-rows: max-content 1fr;
}

.form-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 150px;
  gap: 1rem;
}

.loader {
  width: 42px;
  height: 36px;
  object-fit: contain;
  object-position: center;
}

.border-gray {
  border-color: rgb(60, 75, 95);
}
</style>
