export default [
    {
        path: '/',
        name: 'index',
        component: resolve => require(['./pages/index.vue'], resolve)
    }, {
        path: '/todo',
        name: 'todo',
        meta: { slide: false },
        component: resolve => require(['./pages/todo.vue'], resolve)
    }, {
        path: '/project',
        name: 'project',
        meta: { slide: false },
        component: resolve => require(['./pages/project.vue'], resolve)
    }, {
        path: '/doc',
        name: 'doc',
        meta: { slide: false },
        component: resolve => require(['./pages/doc.vue'], resolve)
    }, {
        path: '/team',
        name: 'team',
        meta: { slide: false },
        component: resolve => require(['./pages/team.vue'], resolve)
    }
]
