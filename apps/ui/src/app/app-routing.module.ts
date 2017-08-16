import { NgModule }             from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import {GridComponent} from "./template/grid.component";
import {NewComponent} from "./template/new.component";
import {DetailsComponent} from "./template/details.component";

const routes: Routes = [
    { path: '', redirectTo: '/job-templates', pathMatch: 'full' },
    { path: 'job-templates',  component: GridComponent },
    { path: 'job-templates/new',  component: NewComponent },
    { path: 'job-template/:id', component: DetailsComponent },
];

@NgModule({
    imports: [ RouterModule.forRoot(routes) ],
    exports: [ RouterModule ]
})
export class AppRoutingModule {}
