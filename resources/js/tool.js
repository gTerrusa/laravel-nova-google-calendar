Nova.booting((Vue, router, store) => {
  router.addRoutes([
    {
      name: 'laravel-nova-google-calendar',
      path: '/laravel-nova-google-calendar',
      component: require('./components/Tool'),
    },
  ])
})
