import { NgModule }             from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import {TimelineComponent} from "./page/timeline.component";
import {TemplateViewComponent} from "./page/template-view.component";
import {TemplateListComponent} from "./page/template-list.component";
import {TemplateNewComponent} from "./page/template-new.component";
import {JobViewComponent} from "./page/job-view.component";
import {LateServerErrorsComponent} from "./page/late-server-errors.component";
import {ApiBaseUrlComponent} from "./page/api-base-url.component";
import {ApiGuard} from "./shared/api.guard";

const routes: Routes = [
    { path: '', redirectTo: '/timeline', pathMatch: 'full' },
    { path: 'timeline', component: TimelineComponent, canActivate: [ApiGuard] },
    { path: 'template/list',  component: TemplateListComponent, canActivate: [ApiGuard] },
    { path: 'template/new',  component: TemplateNewComponent, canActivate: [ApiGuard] },
    { path: 'template/:id/view', redirectTo: 'template/:id/view/summary' },
    { path: 'template/:id/view/:tab', component: TemplateViewComponent, canActivate: [ApiGuard] },
    { path: 'job/:id/view', redirectTo: 'job/:id/view/summary'},
    { path: 'job/:id/view/:tab', component: JobViewComponent, canActivate: [ApiGuard] },
    { path: 'errors/late', component: LateServerErrorsComponent, canActivate: [ApiGuard] },
    { path: 'settings/base-url', component: ApiBaseUrlComponent },
];

@NgModule({
    imports: [ RouterModule.forRoot(routes) ],
    exports: [ RouterModule ]
})
export class AppRoutingModule {}
