export default [
    {
        path: '/',
        name: 'index',
        component: resolve => require(['./pages/index.vue'], resolve)
    }, {
        path: '/todo',
        name: 'todo',
        meta: { slide: false, tabActive: 'todo' },
        component: resolve => require(['./pages/todo.vue'], resolve)
    }, {
        path: '/project',
        name: 'project',
        meta: { slide: false, tabActive: 'project' },
        component: resolve => require(['./pages/project.vue'], resolve)
    }, {
        path: '/project/panel/:projectid',
        name: 'project-panel',
        meta: { slide: false, tabActive: 'project' },
        component: resolve => require(['./pages/project/panel.vue'], resolve)
    }, {
        path: '/docs',
        name: 'docs',
        meta: { slide: false, tabActive: 'docs' },
        component: resolve => require(['./pages/docs.vue'], resolve)
    }, {
        path: '/docs/edit/:sid',
        name: 'docs-edit',
        meta: { slide: false },
        component: resolve => require(['./pages/docs/edit.vue'], resolve)
    }, {
        path: '/team',
        name: 'team',
        meta: { slide: false, tabActive: 'team' },
        component: resolve => require(['./pages/team.vue'], resolve)
    }
]
