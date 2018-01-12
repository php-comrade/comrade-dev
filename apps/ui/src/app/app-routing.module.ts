import { NgModule }             from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import {TimelineComponent} from "./page/timeline.component";
import {TemplateViewComponent} from "./page/template-view.component";
import {TemplateListComponent} from "./page/template-list.component";
import {TemplateNewComponent} from "./page/template-new.component";
import {JobViewComponent} from "./page/job-view.component";
import {LateServerErrorsComponent} from "./page/late-server-errors.component";
import {ApiBaseUrlComponent} from "./page/api-base-url.component";
import {LandingComponent} from "./page/landing.component";
import {WampGuard} from "./shared/wamp.guard";

const routes: Routes = [
    { path: '', component: LandingComponent },
    { path: 'timeline', component: TimelineComponent, canActivate: [WampGuard] },
    { path: 'template/list',  component: TemplateListComponent, canActivate: [WampGuard] },
    { path: 'template/new',  component: TemplateNewComponent, canActivate: [WampGuard] },
    { path: 'template/:id/view', redirectTo: 'template/:id/view/summary' },
    { path: 'template/:id/view/:tab', component: TemplateViewComponent, canActivate: [WampGuard] },
    { path: 'job/:id/view', redirectTo: 'job/:id/view/summary'},
    { path: 'job/:id/view/:tab', component: JobViewComponent, canActivate: [WampGuard] },
    { path: 'errors/late', component: LateServerErrorsComponent, canActivate: [WampGuard] },
    { path: 'settings/base-url', component: ApiBaseUrlComponent },
];

@NgModule({
    imports: [ RouterModule.forRoot(routes) ],
    exports: [ RouterModule ]
})
export class AppRoutingModule {}
