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

    <div
      class="attendee-grid py-2"
      :class="{ noform: addAttendeesDisabled.includes(calendar.summary) }"
    >
      <form
        v-if="!addAttendeesDisabled.includes(calendar.summary)"
        action="#"
        method="post"
        @submit.prevent="addAttendee"
        class="pb-4 border-b border-gray"
      >
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

        <div class="overflow-auto p-3">
          <p class="py-1" v-if="!attendees.length">There are no attendees for this event..</p>

          <div class="attendee-list-grid items-start py-1" v-for="attendee in attendees">
            <div
              v-show="!hiddenData.includes('name')"
            >
              <h4 class="py-1">Name</h4>
              <input
                :value="attendee.displayName || attendee.email.split('@')[0]"
                type="text"
                disabled
                class="form-control form-input form-input-bordered my-2 max-w-full"
              >
            </div>

            <div
              v-show="!hiddenData.includes('email')"
            >
              <h4 class="py-1">Email</h4>
              <input
                :value="attendee.email"
                type="text"
                disabled
                class="form-control form-input form-input-bordered my-2 max-w-full"
              >
            </div>

            <div
              v-show="!hiddenData.includes('no_show')"
            >
              <h4 class="py-1">No Show</h4>
              <input
                type="checkbox"
                :checked="isNoShow(attendee)"
                @click="toggleNoShow(attendee)"
                class="my-2"
              >
            </div>

            <div
              v-show="!hiddenData.includes('status')"
            >
              <h4 class="py-1">Status</h4>

              <select
                v-model="attendee.responseStatus"
                class="form-control form-input form-input-bordered my-2 max-w-full"
              >
                <option value="needsAction">Needs Action</option>
                <option value="accepted">Accepted</option>
                <option value="declined">Declined</option>
                <option value="tentative">Tentative</option>
              </select>
            </div>

            <div
              v-for="item in additionalData"
              :key="item.field"
            >
              <h4 class="py-1">{{ item.label }}</h4>

              <input
                :type="item.input"
                v-model="attendee[item.field]"
                :class="!['checkbox', 'radio'].includes(item.input) ? 'form-control form-input form-input-bordered my-2 max-w-full' : 'my-2'"
              >
            </div>

            <button
              class="btn btn-default btn-info mb-2 mt-auto"
              @click="$root.$emit('reschedule', attendee)"
            >
              <span>Reschedule</span>
            </button>

            <button
              class="btn btn-default btn-primary mb-2 mt-auto col-start-4"
              @click="updateStatus(attendee)"
            >
              <span v-if="!attendeeLoading">Save</span>
              <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
            </button>
          </div>
        </div>
      </div>

      <div class="pt-4">
        <button class="btn btn-default btn-primary" @click="downloadAttendees">
          <span v-if="!downloadLoading">Download</span>
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
    event: { type: Object, required: true },
    calendar: { type: Object, required: true },
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
      downloadLoading: false,
      attendeeLoading: false,
      addAttendeesDisabled: Nova.config.add_attendees_disabled || [],
    }
  },

  computed: {
    hiddenData () {
      return Nova.config.attendee_hidden_info
    },
    additionalData () {
      return (Nova.config.db_attendee_additional_info && Array.isArray(Nova.config.db_attendee_additional_info))
        ? Nova.config.db_attendee_additional_info.filter(item => item.calendars && Array.isArray(item.calendars) && item.calendars.includes(this.calendar.summary)) || []
        : []
    },
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

  async mounted() {
    if (Nova.config.save_attendees_to_db && Nova.config.fetch_db_attendee_additional_info_path && this.additionalData && this.additionalData.length) {
      try {
        const { data } = await axios.post(Nova.config.fetch_db_attendee_additional_info_path, {
          attendees: this.attendees
        })

        this.attendees = data
      } catch (e) {
        console.log(e)
      }
    }
  },

  methods: {
    isNoShow (attendee) {
      return !!(attendee.comment && attendee.comment === 'No Show')
    },
    toggleNoShow (attendee) {
      attendee.comment = (this.isNoShow(attendee)) ? '' : 'No Show'
    },
    formatAppointment (attendee) {
      let appointment = {
        calendar_name: this.calendar.summary,
        event_name: this.event.event.extendedProps.summary,
        event_start: this.event.event.extendedProps.googleStart.date || this.event.event.extendedProps.googleStart.dateTime,
        response_status: attendee.responseStatus,
        no_show: this.isNoShow(attendee)
      }

      if (this.additionalData && this.additionalData.length) {
        let additional_data = {}

        this.additionalData.forEach((item) => {
          if (typeof attendee[item.field] !== 'undefined') {
            additional_data[item.field] = attendee[item.field]
          }
        })

        appointment.additional_data = additional_data
      }

      return appointment
    },
    async saveAttendeeToDB(attendee) {
      if (Nova.config.save_attendees_to_db) {
        return await axios.post(Nova.config.attendee_create_or_update_path, {
          email: attendee.email,
          name: attendee.displayName,
          calendar_id: this.form.calendar_id,
          event_id: this.form.id,
          appointment: this.formatAppointment(attendee)
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
        var { data } = await this.addAttendeeToCalendar()
      } catch (err) {
        window.alert(err.response.data.message)
        this.loading = false
        return
      }

      try {
        await this.saveAttendeeToDB(this.form.attendee)
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
    async updateStatus(attendee) {
      this.attendeeLoading = true
      var errors = []

      try {
        var { data } = await axios.post('/api/google-calendar/calendars/events/attendees/update', {
          id: this.event.event.id,
          calendar_id: this.event.event.extendedProps.calendar_id || null,
          attendee: attendee
        })
      } catch (err) {
        console.log(err)
        if (err && err.response && err.response.data) {
          errors.push(err.response.data)
        } else {
          errors.push('Error updating attendee in google calendar.')
        }
      }

      try {
        await this.saveAttendeeToDB(attendee)
      } catch (err) {
        console.log(err)
        if (err && err.response && err.response.data) {
          errors.push(err.response.data)
        } else {
          errors.push('Error updating attendee in database.')
        }
      }

      this.attendeeLoading = false

      if (errors && errors.length) {
        errors.forEach((error) => {
          Nova.error(error)
        })
      } else {
        Nova.success('Attendee Updated!')
        this.$emit('attendeeAdded', { attendee, data });
      }
    },
    downloadAttendees() {
      this.downloadLoading = true;
      
      const start = this.event.event.extendedProps.googleStart.dateTime || this.event.event.extendedProps.googleStart.date
      const end = this.event.event.extendedProps.googleEnd.dateTime || this.event.event.extendedProps.googleEnd.date
      
      axios.post('/api/google-calendar/calendars/events/attendees/download', {
        calendar_id: this.event.event.extendedProps.calendar_id,
        event_id: this.event.event.id,
        start,
        end
      })
        .then(r => {
          fileDownload(r.data, this.fileName);
          this.downloadLoading = false;
        })
        .catch(e => {
          console.log(e);
          this.downloadLoading = false;
        });
    }
  }
}
</script>

<style scoped>
.attendee-grid {
  display: grid;
  grid-template-rows: max-content 300px max-content;
  gap: 1rem;
  overflow: hidden;
}

.attendee-grid.noform {
  grid-template-rows: 300px max-content;
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

.attendee-list-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
  padding: 15px 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(60, 75, 95, .5);
  border-radius: 10px;
}

.attendee-list-grid:last-of-type {
  margin-bottom: 0;
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

.col-start-4 {
  grid-column-start: 4;
}
</style>
