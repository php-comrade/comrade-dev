import { NgModule }             from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import {TimelineComponent} from "./timeline/timeline.component";
import {JobDetailsComponent} from "./page/job-details.component";
import {JobGridComponent} from "./page/job-grid.component";
import {JobNewComponent} from "./page/job-new.component";

const routes: Routes = [
    { path: '', redirectTo: '/timeline', pathMatch: 'full' },
    { path: 'timeline', component: TimelineComponent },
    { path: 'job',  component: JobGridComponent },
    { path: 'job/new',  component: JobNewComponent },
    { path: 'job/:id', component: JobDetailsComponent },
];

@NgModule({
    imports: [ RouterModule.forRoot(routes) ],
    exports: [ RouterModule ]
})
export class AppRoutingModule {}
