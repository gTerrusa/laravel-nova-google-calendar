<template>
  <div class="cal-modal" @click.self="$emit('close')">
    <div class="modal-content card p-8">
      <button class="close" @click="$emit('close')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path class="heroicon-ui" d="M16.24 14.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 0 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12l2.83 2.83z"/></svg>
      </button>

      <form action="#" method="post" @submit.prevent="createNewCalendar" class="p-8">
        <h3 class="mb-6">Create New Calendar</h3>

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
          <span class="block mb-2 text-80 leading-tight">Description:</span>
          <textarea
            name="description"
            v-model="form.description"
            class="w-full form-input form-input-bordered p-2"
            rows="3"
          ></textarea>
        </label>

        <div class="pt-4">
          <button type="submit" class="btn btn-default btn-primary mr-4">
            <span v-if="!loading">Create Calendar</span>
            <img v-else src="/images/loading/Spinner.gif" alt="loading" class="loader">
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NewCalModal',

  data() {
    return {
      form: {
        title: '',
        description: ''
      },
      loading: false
    }
  },

  methods: {
    createNewCalendar() {
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
