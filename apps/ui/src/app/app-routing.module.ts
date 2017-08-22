import { NgModule }             from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import {TimelineComponent} from "./page/timeline.component";
import {TemplateViewComponent} from "./page/template-view.component";
import {TemplateListComponent} from "./page/template-list.component";
import {TemplateNewComponent} from "./page/template-new.component";
import {JobViewComponent} from "./page/job-view.component";

const routes: Routes = [
    { path: '', redirectTo: '/timeline', pathMatch: 'full' },
    { path: 'timeline', component: TimelineComponent },
    { path: 'template/list',  component: TemplateListComponent },
    { path: 'template/new',  component: TemplateNewComponent },
    { path: 'template/:id/view', component: TemplateViewComponent },
    { path: 'template/:id/view/:tab', component: TemplateViewComponent },
    { path: 'job/:id/view', component: JobViewComponent },
];

@NgModule({
    imports: [ RouterModule.forRoot(routes) ],
    exports: [ RouterModule ]
})
export class AppRoutingModule {}
