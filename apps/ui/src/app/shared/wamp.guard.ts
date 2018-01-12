import {Injectable} from "@angular/core";
import {CanActivate} from "@angular/router";

import 'rxjs/add/operator/map';
import 'rxjs/add/operator/do';
import {WampService} from "./wamp.service";

@Injectable()
export class WampGuard implements CanActivate {
    constructor(private wampService: WampService) {}

    canActivate() {
        return this.wampService.getWampBaseUrl().map(wampBaseUrl => !!wampBaseUrl);
    }
}
