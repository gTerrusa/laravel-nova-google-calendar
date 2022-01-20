<template>
  <div class="attendee-download-modal" @click.self="$emit('close')">
    <div class="modal-content card p-8">
      <button class="close" @click="$emit('close')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path class="heroicon-ui" d="M16.24 14.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 0 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12l2.83 2.83z"/></svg>
      </button>

      <form action="#" method="post" @submit.prevent="downloadAttendees" class="p-8">
        <h3 class="mb-6">Download Attendees</h3>

        <label class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Calendar:</span>
          <select v-model="form.calendar_id" class="w-full form-control form-input form-input-bordered my-2" required>
            <option
              v-for="calendar in calendars"
              v-if="calendar.summary !== 'Holidays in United States'"
              :value="calendar.id"
            >
              {{ calendar.summary }}
            </option>
          </select>
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

        <div class="pt-4">
          <button type="submit" class="btn btn-default btn-primary mr-4">
            <span v-if="!loading">Download Attendees</span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
const fileDownload = require('js-file-download');

export default {
  name: 'AttendeeDownloadModal',

  props: {
    calendars: { type: Array, required: true }
  },

  data() {
    return {
      form: {
        calendar_id: '',
        event_id: null,
        start: null,
        end: null
      },
      loading: false
    }
  },

  computed: {
    calendar() {
      return this.calendars.find(cal => cal.id === this.form.calendar_id);
    },

    fileName() {
      return this.calendar.summary + '_' + 'events_attendees.csv';
    }
  },

  methods: {
    downloadAttendees() {
      this.loading = true;
      axios.post('/api/google-calendar/calendars/events/attendees/download', this.form)
        .then(r => {
          fileDownload(r.data, this.fileName);
          this.loading = false;
          this.$emit('close');
        })
        .catch(e => {
          console.log(e);
          this.loading = false;
        });
    },
    changeStart(value) {
      this.form.start = value;
    },
    changeEnd(value) {
      this.form.end = value;
    },
  }
}
</script>

<style scoped>
.attendee-download-modal {
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
  max-height: 90%;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.5);
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

.w-max {
  width: max-content;
}

.loader {
  width: 106px;
  height: 36px;
  object-fit: contain;
  object-position: center;
}
</style>
