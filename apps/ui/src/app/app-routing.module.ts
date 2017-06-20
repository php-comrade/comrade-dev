import { NgModule }             from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import {GridComponent} from "./template/grid.component";

const routes: Routes = [
    { path: '', redirectTo: '/job-templates', pathMatch: 'full' },
    { path: 'job-templates',  component: GridComponent },
];

@NgModule({
    imports: [ RouterModule.forRoot(routes) ],
    exports: [ RouterModule ]
})
export class AppRoutingModule {}
