<template>
  <div class="cal-modal" @click.self="$emit('close')">
    <div class="modal-content card p-8">
      <button class="close" @click="$emit('close')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path class="heroicon-ui" d="M16.24 14.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 0 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12l2.83 2.83z"/></svg>
      </button>

      <form action="#" method="post" @submit.prevent="onSubmit" class="p-8">
        <h3 class="mb-6">
          {{ calendar ? 'Update Calendar' : 'Create New Calendar' }}
        </h3>

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

        <label class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Subtitle:</span>
          <input
            type="text"
            name="title"
            v-model="form.subtitle"
            class="w-full form-control form-input form-input-bordered"
          >
        </label>

        <label class="block py-2">
          <span class="block mb-2 text-80 leading-tight">Url:</span>
          <input
            type="text"
            name="title"
            v-model="computedUrl"
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

        <div class="pt-4 flex items-center">
          <button type="submit" class="btn btn-default btn-primary mr-4">
            <span v-if="!loading">
              {{ calendar ? 'Update Calendar' : 'Create Calendar' }}
            </span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>

          <button v-if="calendar && !calendar.primary" class="btn btn-default btn-danger" @click.prevent="deleteCalendar">
            <span v-if="!loadingDelete">Delete Calendar</span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { slugify } from '../helpers/slugify'
import { tryJsonParse } from '../helpers/tryJsonParse'

export default {
  name: 'CalModal',

  data() {
    return {
      form: {
        title: '',
        subtitle: '',
        url: '',
        description: ''
      },
      loading: false,
      loadingDelete: false
    }
  },

  props: {
    calendar: { type: Object, default: null }
  },

  computed: {
    computedUrl: {
      get () {
        return this.form.url || slugify(this.form.title)
      },

      set (val) {
        this.$set(this.form, 'url', val)
      }
    }
  },

  mounted () {
    if (this.calendar) {
      const parsedDesc = tryJsonParse(this.calendar.description)

      this.form.title = this.calendar.summary
      this.form.subtitle = parsedDesc.subtitle || ''
      this.form.url = parsedDesc.url || ''

      if (parsedDesc.description) {
        this.form.description = parsedDesc.description
      } else if (typeof parsedDesc !== 'object') {
        this.form.description = parsedDesc
      } else {
        this.form.description = ''
      }
    }
  },

  methods: {
    onSubmit () {
      if (this.calendar) {
        this.updateCalendar()
      } else {
        this.createNewCalendar()
      }
    },

    createNewCalendar () {
      this.loading = true;
      axios.post('/api/google-calendar/calendars/create', this.form)
        .then(r => {
          this.$emit('updateCalendars', { calendars: r.data.calendars, close: true });
          this.loading = false;
        })
        .catch(e => {
          console.log(e);
          this.loading = false;
        });
    },

    updateCalendar () {
      this.loading = true

      let data = {
        id: this.calendar.id,
        summary: this.form.title,
        description: {
          url: this.form.url || slugify(this.form.title)
        }
      }

      if (this.form.description) {
        data.description.description = this.form.description
      }

      if (this.form.subtitle) {
        data.description.subtitle = this.form.subtitle
      }

      axios.post('/api/google-calendar/calendars/update', data)
        .then(r => {
          this.$emit('updateCalendars', { calendars: r.data.calendars, close: true })
          this.loading = false
        })
        .catch(e => {
          console.log(e)
          this.loading = false
        })
    },

    deleteCalendar () {
      this.loadingDelete = true

      axios.post('/api/google-calendar/calendars/delete', { id: this.calendar.id })
        .then(r => {
          this.$emit('updateCalendars', { calendars: r.data.calendars, close: true })
          this.loadingDelete = false
        })
        .catch(e => {
          console.log(e)
          this.loadingDelete = false
        })
    }
  }
}
</script>

<style scoped>
.cal-modal {
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
